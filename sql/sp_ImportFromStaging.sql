USE [DBMOVESA]
GO
/****** Object:  StoredProcedure [dbo].[sp_ImportFromStaging]    Script Date: 26/8/2025 17:14:01 ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

ALTER   PROCEDURE [dbo].[sp_ImportFromStaging]
  @OnlyUnimported BIT = 1,  -- si =1 procesa sólo filas con Importado = 0
  @BatchSize INT = 5000     -- número de filas a procesar por batch (ajustado a tu carga)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    DECLARE @AnyRows INT = 1;
    DECLARE @LoopCounter INT = 0;

    WHILE (@AnyRows = 1)
    BEGIN
        SET @LoopCounter = @LoopCounter + 1;

        BEGIN TRANSACTION;
        BEGIN TRY
            -- Tomar un batch determinístico
            IF OBJECT_ID('tempdb..#Batch') IS NOT NULL DROP TABLE #Batch;

            SELECT TOP (@BatchSize) 
                RowId, DOCENTRY, DOCNUM, NUMERO_PRESTAMO, ALMACEN_ENTREGA, DOCDATE, DIA, MES, ANIO,
                CARDCODE, CARDNAME, GROUPCODE, TIPO, QUANTITY, TOTAL_USD,
                SERIE, U_AMODELO, MODELO, U_AMARCA, MARCA, U_ACOLOR, COLOR, SEGMENTO,
                VENDEDOR, NOMBRE_VENDEDOR, SUPERVISOR, [ALMACEN VENTA], NOMBRE_ALMACEN_VENTA, CANAL
            INTO #Batch
            FROM dbo.StagingVentasImport s
            WHERE ( @OnlyUnimported = 0 OR s.Importado = 0 )
            ORDER BY RowId;

            IF NOT EXISTS (SELECT 1 FROM #Batch)
            BEGIN
                DROP TABLE #Batch;
                COMMIT TRANSACTION;
                BREAK; -- nada por procesar
            END

            -- 1) Insertar catálogos desde #Batch con deduplicación (GROUP BY / MIN para estabilidad)
            -- Marcas
            INSERT INTO dbo.Marcas (Codigo, Nombre)
            SELECT b.U_AMARCA, MIN(b.MARCA)
            FROM #Batch b
            WHERE b.U_AMARCA IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Marcas m WHERE m.Codigo = b.U_AMARCA)
            GROUP BY b.U_AMARCA;

            -- Colores
            INSERT INTO dbo.Colores (Codigo, Nombre)
            SELECT b.U_ACOLOR, MIN(b.COLOR)
            FROM #Batch b
            WHERE b.U_ACOLOR IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Colores c WHERE c.Codigo = b.U_ACOLOR)
            GROUP BY b.U_ACOLOR;

            -- Segmentos
            INSERT INTO dbo.Segmentos (Nombre)
            SELECT DISTINCT b.SEGMENTO
            FROM #Batch b
            WHERE b.SEGMENTO IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Segmentos s WHERE s.Nombre = b.SEGMENTO);

            -- Canales
            INSERT INTO dbo.Canales (Codigo, Nombre)
            SELECT DISTINCT b.CANAL, MIN(b.CANAL)
            FROM #Batch b
            WHERE b.CANAL IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Canales ca WHERE ca.Codigo = b.CANAL)
            GROUP BY b.CANAL;

            -- Almacenes
            INSERT INTO dbo.Almacenes (Codigo, Nombre)
            SELECT t.Codigo, MIN(t.Nombre)
            FROM (
                SELECT b.ALMACEN_ENTREGA AS Codigo, NULL AS Nombre FROM #Batch b WHERE b.ALMACEN_ENTREGA IS NOT NULL
                UNION ALL
                SELECT b.[ALMACEN VENTA] AS Codigo, b.NOMBRE_ALMACEN_VENTA AS Nombre FROM #Batch b WHERE b.[ALMACEN VENTA] IS NOT NULL
            ) t
            WHERE NOT EXISTS (SELECT 1 FROM dbo.Almacenes a WHERE a.Codigo = t.Codigo)
            GROUP BY t.Codigo;

            -- Vendedores
            INSERT INTO dbo.Vendedores (Codigo, Nombre, Supervisor)
            SELECT b.VENDEDOR, MIN(b.NOMBRE_VENDEDOR), MIN(b.SUPERVISOR)
            FROM #Batch b
            WHERE b.VENDEDOR IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Vendedores v WHERE v.Codigo = b.VENDEDOR)
            GROUP BY b.VENDEDOR;

            -- Clientes
            INSERT INTO dbo.Clientes (CardCode, CardName, GroupCode)
            SELECT b.CARDCODE, MIN(b.CARDNAME), MIN(b.GROUPCODE)
            FROM #Batch b
            WHERE b.CARDCODE IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Clientes c WHERE c.CardCode = b.CARDCODE)
            GROUP BY b.CARDCODE;

            -- Modelos
            INSERT INTO dbo.Modelos (Codigo, Nombre, MarcaId)
            SELECT b.U_AMODELO, MIN(b.MODELO), m.MarcaId
            FROM #Batch b
            LEFT JOIN dbo.Marcas m ON m.Codigo = b.U_AMARCA
            WHERE b.U_AMODELO IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Modelos mo WHERE mo.Codigo = b.U_AMODELO)
            GROUP BY b.U_AMODELO, m.MarcaId;

            -- 2) Productos por SERIE (mapear ids)
            INSERT INTO dbo.Productos (Serie, U_AMODELO, U_AMARCA, U_ACOLOR, ModeloId, MarcaId, ColorId, SegmentoId)
            SELECT DISTINCT b.SERIE, b.U_AMODELO, b.U_AMARCA, b.U_ACOLOR,
                mo.ModeloId, ma.MarcaId, co.ColorId, se.SegmentoId
            FROM #Batch b
            LEFT JOIN dbo.Modelos mo ON mo.Codigo = b.U_AMODELO
            LEFT JOIN dbo.Marcas ma ON ma.Codigo = b.U_AMARCA
            LEFT JOIN dbo.Colores co ON co.Codigo = b.U_ACOLOR
            LEFT JOIN dbo.Segmentos se ON se.Nombre = b.SEGMENTO
            WHERE b.SERIE IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM dbo.Productos p WHERE p.Serie = b.SERIE);

            -- 3) Ventas: insertar filas en Ventas mapeando ids (evitar duplicados por DocEntry/DocNum)
            INSERT INTO dbo.Ventas (
                DocEntry, DocNum, Numero_Prestamo, Almacen_Entrega_Codigo, Almacen_Entrega_Id,
                DocDate, Dia, Mes, Anio, ClienteId, ClienteCardCode, Tipo,
                Quantity, Total_USD, GrupoCode, ProductoId, VendedorId,
                Almacen_VentaId, CanalId, Supervisor, Nombre_Almacen
            )
            SELECT
                b.DOCENTRY,
                b.DOCNUM,
                b.NUMERO_PRESTAMO,
                b.ALMACEN_ENTREGA,
                a_en.AlmacenId,
                TRY_CAST(b.DOCDATE AS DATE),
                b.DIA,
                b.MES,
                b.ANIO,
                c.ClienteId,
                b.CARDCODE,
                b.TIPO,
                b.QUANTITY,
                b.TOTAL_USD,
                b.GROUPCODE,
                p.ProductoId,
                v.VendedorId,
                a_venta.AlmacenId,
                ch.CanalId,
                b.SUPERVISOR,
                b.NOMBRE_ALMACEN_VENTA
            FROM #Batch b
            LEFT JOIN dbo.Clientes c ON c.CardCode = b.CARDCODE
            LEFT JOIN dbo.Productos p ON p.Serie = b.SERIE
            LEFT JOIN dbo.Vendedores v ON v.Codigo = b.VENDEDOR
            LEFT JOIN dbo.Almacenes a_venta ON a_venta.Codigo = b.[ALMACEN VENTA]
            LEFT JOIN dbo.Almacenes a_en ON a_en.Codigo = b.ALMACEN_ENTREGA
            LEFT JOIN dbo.Canales ch ON ch.Codigo = b.CANAL
            WHERE NOT EXISTS (
                SELECT 1 FROM dbo.Ventas x
                WHERE (x.DocEntry IS NOT NULL AND b.DOCENTRY IS NOT NULL AND x.DocEntry = b.DOCENTRY)
                   OR (x.DocNum IS NOT NULL AND b.DOCNUM IS NOT NULL AND x.DocNum = b.DOCNUM)
            );

            -- 4) Marcar filas del batch como importadas y limpiar errores previos
            UPDATE s
            SET Importado = 1, ErrorMensaje = NULL
            FROM dbo.StagingVentasImport s
            INNER JOIN #Batch b ON s.RowId = b.RowId;

            COMMIT TRANSACTION;

            -- Continuar si hay más filas
            IF EXISTS (SELECT 1 FROM dbo.StagingVentasImport s WHERE ( @OnlyUnimported = 0 OR s.Importado = 0 ))
                SET @AnyRows = 1;
            ELSE
                SET @AnyRows = 0;

            DROP TABLE IF EXISTS #Batch;

        END TRY
        BEGIN CATCH
            IF XACT_STATE() <> 0 ROLLBACK TRANSACTION;

            DECLARE @ErrMsg NVARCHAR(4000) = ERROR_MESSAGE();
            DECLARE @ErrLine INT = ERROR_LINE();

            -- Registrar error en las filas del batch
            IF OBJECT_ID('tempdb..#Batch') IS NOT NULL
            BEGIN
                UPDATE s
                SET ErrorMensaje = LEFT(@ErrMsg + ' Line:' + CAST(@ErrLine AS VARCHAR(10)), 1000)
                FROM dbo.StagingVentasImport s
                INNER JOIN #Batch b ON s.RowId = b.RowId;
            END

            DROP TABLE IF EXISTS #Batch;

            RAISERROR('Error en sp_ImportFromStaging (batch %d): %s', 16, 1, @LoopCounter, @ErrMsg);
            RETURN;
        END CATCH

    END -- end while

    -- Resultado resumido
    SELECT
        (SELECT COUNT(*) FROM dbo.Marcas) AS TotalMarcas,
        (SELECT COUNT(*) FROM dbo.Modelos) AS TotalModelos,
        (SELECT COUNT(*) FROM dbo.Productos) AS TotalProductos,
        (SELECT COUNT(*) FROM dbo.Ventas) AS TotalVentas,
        (SELECT COUNT(*) FROM dbo.StagingVentasImport WHERE Importado = 1) AS FilasImportadas;
END;
GO
