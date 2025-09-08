/*
 dbo.sp_sales_report
 Filtra y agrupa ventas usando los campos DIA, MES, ANIO (no usa DocDate).
 Soporta @PeriodLevel = 'diario'|'semanal'|'anual'|'mensual'.
 Devuelve filas por periodo (día/semana/año) y añade una fila 'Total' por cada grupo.
 Parámetros:
  @StartDate DATE, @EndDate DATE  -- usados para construir rango sobre DIA/MES/ANIO
  @PeriodLevel NVARCHAR(20) = 'diario'
  @GroupBy NVARCHAR(20) = 'sucursal' -- 'sucursal'|'zona'|'vendedor'|'modelo'|'general'
  @Canal NVARCHAR(50) = NULL -- filtro opcional
  @IncludeMatrix BIT = 0 -- si 1 y PeriodLevel='mensual' puede devolver D01..D31 (no implementado aquí)

Notas: Este procedimiento asume que la tabla dbo.Ventas contiene columnas enteras
      llamadas DIA, MES, ANIO y una columna numérica Total_USD.
*/
CREATE OR ALTER PROCEDURE dbo.sp_sales_report
    @StartDate DATE = NULL,
    @EndDate   DATE = NULL,
    @PeriodLevel NVARCHAR(20) = 'diario',
    @GroupBy NVARCHAR(20) = 'sucursal',
    @Canal NVARCHAR(50) = NULL,
    @IncludeMatrix BIT = 0,
    @ReportType NVARCHAR(20) = 'resumen' -- 'resumen' o 'detallado'
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @sql NVARCHAR(MAX);

    -- Validaciones simples
    IF @StartDate IS NULL OR @EndDate IS NULL
    BEGIN
        RAISERROR('Debe especificar @StartDate y @EndDate', 16, 1);
        RETURN;
    END

    -- Convertir rango a valor numérico YYYYMMDD para comparar con DIA/MES/ANIO
    DECLARE @startNum INT = YEAR(@StartDate) * 10000 + MONTH(@StartDate) * 100 + DAY(@StartDate);
    DECLARE @endNum   INT = YEAR(@EndDate)   * 10000 + MONTH(@EndDate)   * 100 + DAY(@EndDate);

    -- Nota: el parámetro @IncludeMatrix ya no se usa; forzamos a 0 para evitar generar pivotes muy anchos.
    SET @IncludeMatrix = 0;

    -- Materializar dataset base en #Source usando DIA/MES/ANIO
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL DROP TABLE #Source;

    SELECT
        V.*,
        (V.ANIO * 10000 + V.MES * 100 + V.DIA) AS DateNum,
        TRY_CAST(DATEFROMPARTS(V.ANIO, V.MES, V.DIA) AS DATE) AS DateCalc,
        DATEPART(week, TRY_CAST(DATEFROMPARTS(V.ANIO, V.MES, V.DIA) AS DATE)) AS WeekOfYear
    INTO #Source
    FROM dbo.Ventas AS V
    WHERE V.ANIO IS NOT NULL AND V.MES IS NOT NULL AND V.DIA IS NOT NULL
      AND (V.ANIO * 10000 + V.MES * 100 + V.DIA) BETWEEN @startNum AND @endNum
      -- Filtrado por canal: uso solo CanalId (convertido a texto) para evitar referencias a columnas inexistentes
      AND (
          @Canal IS NULL
          OR ISNULL(CAST(V.CanalId AS NVARCHAR(100)), '') = @Canal
      );

    -- Asegurar columnas esenciales en #Source para evitar errores de compilación dinámica
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        IF COL_LENGTH('tempdb..#Source','VendedorNombre') IS NULL
            ALTER TABLE #Source ADD VendedorNombre NVARCHAR(200) NULL;

        IF COL_LENGTH('tempdb..#Source','VendedorGroupId') IS NULL
            ALTER TABLE #Source ADD VendedorGroupId INT NULL;

        IF COL_LENGTH('tempdb..#Source','VendedorId') IS NULL
            ALTER TABLE #Source ADD VendedorId INT NULL;

        IF COL_LENGTH('tempdb..#Source','Total_USD') IS NULL
            ALTER TABLE #Source ADD Total_USD DECIMAL(18,2) NULL;

        -- Inicializar valores nulos para evitar NULLs en agregaciones
        UPDATE #Source SET VendedorId = ISNULL(VendedorId, 0), Total_USD = ISNULL(Total_USD, 0);

        -- Defensive: asegurar otras columnas usadas por los agrupamientos existen y están inicializadas
        IF COL_LENGTH('tempdb..#Source','Nombre_Almacen') IS NULL
            ALTER TABLE #Source ADD Nombre_Almacen NVARCHAR(200) NULL;

        IF COL_LENGTH('tempdb..#Source','SucursalGroupId') IS NULL
            ALTER TABLE #Source ADD SucursalGroupId INT NULL;

        IF COL_LENGTH('tempdb..#Source','ModeloGroupId') IS NULL
            ALTER TABLE #Source ADD ModeloGroupId INT NULL;

        UPDATE #Source
        SET Nombre_Almacen = ISNULL(CAST(Nombre_Almacen AS NVARCHAR(200)), ''),
            VendedorNombre = ISNULL(VendedorNombre, ''),
            SucursalGroupId = ISNULL(SucursalGroupId, 0),
            ModeloGroupId = ISNULL(ModeloGroupId, 0),
            VendedorGroupId = ISNULL(VendedorGroupId, 0),
            VendedorId = ISNULL(VendedorId, 0),
            Total_USD = ISNULL(Total_USD, 0);
    END

    -- Nota: creación de #SourceDyn revertida; el procedimiento usará #Source directamente

    -- Si no hay datos, devolver vacío
    IF NOT EXISTS (SELECT 1 FROM #Source)
    BEGIN
        SELECT CAST(NULL AS NVARCHAR(100)) AS GroupKey, CAST(NULL AS NVARCHAR(100)) AS GroupLabel, CAST(NULL AS NVARCHAR(50)) AS Period, CAST(0.0 AS DECIMAL(18,2)) AS Monto WHERE 1=0;
        RETURN;
    END

    -- Asegurar que #Source tenga columna Total_USD (defensiva contra esquemas distintos)
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        IF COL_LENGTH('tempdb..#Source','Total_USD') IS NULL
            ALTER TABLE #Source ADD Total_USD DECIMAL(18,2) NULL;
        -- Inicializar valores nulos a 0 para evitar errores en agregaciones
        UPDATE #Source SET Total_USD = ISNULL(Total_USD, 0);
    END

    -- Detectar si las columnas de nombre existen en dbo.Ventas y preparar expresiones seguras
    DECLARE @hasNombreVendedor BIT = 0, @hasNombreAlmacen BIT = 0;
    -- Comprobar columna en la tabla temporal #Source (más fiable tras el SELECT INTO)
    SELECT @hasNombreVendedor = CASE WHEN COL_LENGTH('tempdb..#Source','NOMBRE_VENDEDOR') IS NOT NULL THEN 1 ELSE 0 END;
    SELECT @hasNombreAlmacen = CASE WHEN EXISTS(SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.Ventas') AND name = 'Nombre_Almacen') THEN 1 ELSE 0 END;

    DECLARE @sucursalExpr NVARCHAR(200) = '';
    DECLARE @vendedorExpr NVARCHAR(200) = '';
    DECLARE @modeloExpr NVARCHAR(200) = '';

    IF @hasNombreAlmacen = 1
        SET @sucursalExpr = 'Nombre_Almacen';
    ELSE IF EXISTS(SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.Ventas') AND name = 'NOMBRE_ALMACEN_VENTA')
        SET @sucursalExpr = 'NOMBRE_ALMACEN_VENTA';
    ELSE
        SET @sucursalExpr = 'ISNULL(Nombre_Almacen, '''')'; -- fallback, may be empty

    IF @hasNombreVendedor = 1
        -- Si la columna original NOMBRE_VENDEDOR existe en #Source, copiaremos su valor a VendedorNombre más abajo
        SET @vendedorExpr = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
    ELSE
        SET @vendedorExpr = 'CAST(VendedorId AS NVARCHAR(50))';

    -- Añadir columna ModeloNombre a #Source y poblarla desde dbo.Modelos si existe (evita referencias directas a U_AMODELO/MODELO)
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        IF COL_LENGTH('tempdb..#Source','ModeloNombre') IS NULL
            ALTER TABLE #Source ADD ModeloNombre NVARCHAR(200) NULL;

        IF OBJECT_ID('dbo.Modelos') IS NOT NULL
        BEGIN
            -- Llenar ModeloNombre siguiendo la relación correcta: #Source.ProductoId -> dbo.Productos.ProductoId -> dbo.Modelos.ModeloId
            -- Algunas instalaciones almacenan el ModeloId en la tabla Productos, por eso hay que pasar por Productos.
            SET @sql = N'UPDATE s
                        SET s.ModeloNombre = m.Nombre
                        FROM #Source s
                        INNER JOIN dbo.Productos p ON s.ProductoId = p.ProductoId
                        INNER JOIN dbo.Modelos m ON p.ModeloId = m.ModeloId;';
            EXEC sp_executesql @sql;
        END
    END

        -- Añadir columna VendedorNombre a #Source y poblarla desde dbo.Vendedores si existe
        IF OBJECT_ID('tempdb..#Source') IS NOT NULL
        BEGIN
            IF COL_LENGTH('tempdb..#Source','VendedorNombre') IS NULL
                ALTER TABLE #Source ADD VendedorNombre NVARCHAR(200) NULL;

        IF OBJECT_ID('dbo.Vendedores') IS NOT NULL
            BEGIN
                -- Llenar VendedorNombre usando la relación #Source.VendedorId -> dbo.Vendedores.VendedorId
                SET @sql = N'UPDATE s
                            SET s.VendedorNombre = v.Nombre
                            FROM #Source s
                            INNER JOIN dbo.Vendedores v ON s.VendedorId = v.VendedorId;';
                EXEC sp_executesql @sql;
            END

            -- Recalcular la expresión segura de vendedor para preferir VendedorNombre cuando esté disponible
                -- Si la tabla original incluía la columna NOMBRE_VENDEDOR dentro de #Source, copiarla a VendedorNombre
                IF COL_LENGTH('tempdb..#Source','NOMBRE_VENDEDOR') IS NOT NULL
                BEGIN
                    SET @sql = N'UPDATE #Source SET VendedorNombre = ISNULL(CAST(NOMBRE_VENDEDOR AS NVARCHAR(200)), VendedorNombre);';
                    EXEC sp_executesql @sql;
                END

                IF @hasNombreVendedor = 1
                    SET @vendedorExpr = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
                ELSE
                    SET @vendedorExpr = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
        END

    -- Añadir y poblar campos extra del producto (MarcaNombre, Serie, Color, Segmento)
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        IF COL_LENGTH('tempdb..#Source','MarcaNombre') IS NULL
            ALTER TABLE #Source ADD MarcaNombre NVARCHAR(200) NULL;

        IF COL_LENGTH('tempdb..#Source','Serie') IS NULL
            ALTER TABLE #Source ADD Serie NVARCHAR(200) NULL;

        IF COL_LENGTH('tempdb..#Source','Color') IS NULL
            ALTER TABLE #Source ADD Color NVARCHAR(200) NULL;

        IF COL_LENGTH('tempdb..#Source','Segmento') IS NULL
            ALTER TABLE #Source ADD Segmento NVARCHAR(200) NULL;

        -- Poblar desde dbo.Productos y dbo.Marcas si existen
        IF OBJECT_ID('dbo.Productos') IS NOT NULL
        BEGIN
            -- Preferimos usar las tablas relacionadas: dbo.Marcas, dbo.Segmentos y dbo.Colores
            SET @sql = N'UPDATE s
                        SET s.Serie = p.Serie,
                            s.Color = ISNULL(c.Nombre, ISNULL(CAST(p.ColorId AS NVARCHAR(50)), '''')),
                            s.Segmento = ISNULL(seg.Nombre, ISNULL(CAST(p.SegmentoId AS NVARCHAR(50)), '''')),
                            s.MarcaNombre = ISNULL(m.Nombre, '''')
                        FROM #Source s
                        INNER JOIN dbo.Productos p ON s.ProductoId = p.ProductoId
                        LEFT JOIN dbo.Marcas m ON p.MarcaId = m.MarcaId
                        LEFT JOIN dbo.Segmentos seg ON p.SegmentoId = seg.SegmentoId
                        LEFT JOIN dbo.Colores c ON p.ColorId = c.ColorId;';
            EXEC sp_executesql @sql;
        END
    END

    -- Crear un id incremental por nombre de modelo para agrupar modelos con el mismo Nombre
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        IF COL_LENGTH('tempdb..#Source','ModeloGroupId') IS NULL
            ALTER TABLE #Source ADD ModeloGroupId INT NULL;

        ;WITH DistinctNames AS (
            SELECT DISTINCT NormalizedName = ISNULL(NULLIF(LTRIM(RTRIM(UPPER(ModeloNombre))), ''), CAST(ProductoId AS NVARCHAR(50)))
            FROM #Source
        ), Numbered AS (
            SELECT NormalizedName, ROW_NUMBER() OVER (ORDER BY NormalizedName) AS ModelGroupId
            FROM DistinctNames
        )
        UPDATE s
        SET ModeloGroupId = n.ModelGroupId
        FROM #Source s
        JOIN Numbered n ON ISNULL(NULLIF(LTRIM(RTRIM(UPPER(s.ModeloNombre))), ''), CAST(s.ProductoId AS NVARCHAR(50))) = n.NormalizedName;
    END

    -- Crear ids incrementales para sucursal y vendedor (se usan para agrupar por texto de forma estable)
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        IF COL_LENGTH('tempdb..#Source','SucursalGroupId') IS NULL
            ALTER TABLE #Source ADD SucursalGroupId INT NULL;

        IF COL_LENGTH('tempdb..#Source','VendedorGroupId') IS NULL
            ALTER TABLE #Source ADD VendedorGroupId INT NULL;

        -- Poblar SucursalGroupId usando la expresión @sucursalExpr (normalizando texto) y fallback a GrupoCode
        SET @sql = N';WITH DistinctNames AS (
            SELECT DISTINCT NormalizedName = ISNULL(NULLIF(LTRIM(RTRIM(UPPER(' + @sucursalExpr + '))), ''''), CAST(ISNULL(GrupoCode, '''') AS NVARCHAR(50)))
            FROM #Source
        ), Numbered AS (
            SELECT NormalizedName, ROW_NUMBER() OVER (ORDER BY NormalizedName) AS GroupId
            FROM DistinctNames
        )
        UPDATE s
        SET SucursalGroupId = n.GroupId
        FROM #Source s
        JOIN Numbered n ON ISNULL(NULLIF(LTRIM(RTRIM(UPPER(' + @sucursalExpr + '))), ''''), CAST(ISNULL(GrupoCode, '''') AS NVARCHAR(50))) = n.NormalizedName;';
        EXEC sp_executesql @sql;

        -- Poblar VendedorGroupId usando la expresión @vendedorExpr (normalizando texto) y fallback a VendedorId
        SET @sql = N';WITH DistinctNames AS (
            SELECT DISTINCT NormalizedName = ISNULL(NULLIF(LTRIM(RTRIM(UPPER(' + @vendedorExpr + '))), ''''), CAST(VendedorId AS NVARCHAR(50)))
            FROM #Source
        ), Numbered AS (
            SELECT NormalizedName, ROW_NUMBER() OVER (ORDER BY NormalizedName) AS GroupId
            FROM DistinctNames
        )
        UPDATE s
        SET VendedorGroupId = n.GroupId
        FROM #Source s
        JOIN Numbered n ON ISNULL(NULLIF(LTRIM(RTRIM(UPPER(' + @vendedorExpr + '))), ''''), CAST(VendedorId AS NVARCHAR(50))) = n.NormalizedName;';
        EXEC sp_executesql @sql;
    END

    -- Usar la columna sintetizada ModeloNombre (si no se pudo poblar, cae a ProductoId)
    SET @modeloExpr = 'ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))';

    -- Detectar columnas de vendedor/producto en #Source y construir expresiones seguras
    DECLARE @hasVendedorIdInSource BIT = 0, @hasVendedorNombreInSource BIT = 0, @hasVendedorGroupInSource BIT = 0;
    IF OBJECT_ID('tempdb..#Source') IS NOT NULL
    BEGIN
        SELECT @hasVendedorIdInSource = CASE WHEN COL_LENGTH('tempdb..#Source','VendedorId') IS NOT NULL THEN 1 ELSE 0 END;
        SELECT @hasVendedorNombreInSource = CASE WHEN COL_LENGTH('tempdb..#Source','VendedorNombre') IS NOT NULL OR COL_LENGTH('tempdb..#Source','NOMBRE_VENDEDOR') IS NOT NULL THEN 1 ELSE 0 END;
        SELECT @hasVendedorGroupInSource = CASE WHEN COL_LENGTH('tempdb..#Source','VendedorGroupId') IS NOT NULL THEN 1 ELSE 0 END;
    END

    DECLARE @vendedorGroupExpr NVARCHAR(200) = '';
    IF @hasVendedorGroupInSource = 1
        SET @vendedorGroupExpr = 'CAST(ISNULL(VendedorGroupId, 0) AS NVARCHAR(20))';
    ELSE IF @hasVendedorIdInSource = 1
        SET @vendedorGroupExpr = 'CAST(ISNULL(VendedorId, 0) AS NVARCHAR(20))';
    ELSE
        SET @vendedorGroupExpr = '''0''';

    -- Construir @vendedorExpr defensiva según columnas disponibles en #Source
            IF @hasVendedorNombreInSource = 1
    BEGIN
        -- Usar siempre VendedorNombre cuando esté disponible; no referenciar NOMBRE_VENDEDOR directamente
        IF @hasVendedorIdInSource = 1
            SET @vendedorExpr = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
        ELSE
            SET @vendedorExpr = 'ISNULL(VendedorNombre, '''')';
    END
    ELSE
    BEGIN
        IF @hasVendedorIdInSource = 1
            SET @vendedorExpr = 'CAST(VendedorId AS NVARCHAR(50))';
        ELSE
            SET @vendedorExpr = '''SIN_VENDEDOR''';
    END

    -- Mapear GroupBy a expresiones de columna
    DECLARE @groupExpr NVARCHAR(400) = '';
    DECLARE @groupSelect NVARCHAR(400) = '';
    DECLARE @orderBy NVARCHAR(200) = '';
    DECLARE @groupKeyExpr NVARCHAR(200) = '';

    IF LOWER(@GroupBy) = 'sucursal'
    BEGIN
        -- Usar la expresión calculada como etiqueta legible
    SET @groupExpr = @sucursalExpr;
    SET @groupSelect = @sucursalExpr + ' AS GroupLabel, CAST(ISNULL(SucursalGroupId, 0) AS NVARCHAR(20)) AS GroupKey';
    SET @orderBy = @sucursalExpr + ', Period';
    END
    ELSE IF LOWER(@GroupBy) = 'zona' OR LOWER(@GroupBy) = 'grupo' OR LOWER(@GroupBy) = 'zone'
    BEGIN
        SET @groupExpr = 'GrupoCode';
        SET @groupSelect = 'GrupoCode AS GroupLabel, ISNULL(GrupoCode, '''') AS GroupKey';
        SET @orderBy = 'GrupoCode, Period';
    END
    ELSE IF LOWER(@GroupBy) = 'vendedor'
    BEGIN
        -- Mostrar nombre del vendedor si está disponible, agrupar por VendedorId
    -- Preferir la columna sintetizada VendedorNombre y el id incremental VendedorGroupId en #Source
    SET @groupExpr = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
    SET @groupSelect = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50))) AS GroupLabel, CAST(ISNULL(VendedorGroupId, 0) AS NVARCHAR(20)) AS GroupKey';
    SET @orderBy = 'GroupLabel, Period';
    SET @groupKeyExpr = 'CAST(ISNULL(VendedorGroupId, 0) AS NVARCHAR(20))';
    END
    ELSE IF LOWER(@GroupBy) = 'modelo'
    BEGIN
        -- Agrupar por nombre de modelo (ModeloNombre).
        -- Mostrar sólo el Nombre del modelo como etiqueta y usar el id incremental como GroupKey.
        SET @groupExpr = 'ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))';
        SET @groupSelect = 'ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50))) AS GroupLabel, CAST(ISNULL(ModeloGroupId, 0) AS NVARCHAR(20)) AS GroupKey';
        SET @groupKeyExpr = 'CAST(ISNULL(ModeloGroupId, 0) AS NVARCHAR(20))';
        SET @orderBy = 'GroupLabel, Period';
    END
    ELSE -- general
    BEGIN
        SET @groupExpr = '1';
        SET @groupSelect = '''GENERAL'' AS GroupLabel, ''GENERAL'' AS GroupKey';
        SET @orderBy = 'Period';
    END

    -- Asegurar groupKeyExpr para otros casos
    IF @groupKeyExpr = ''
    BEGIN
        IF LOWER(@GroupBy) = 'sucursal'
            -- Usar el id incremental generado en #Source para sucursal como GroupKey
            SET @groupKeyExpr = 'CAST(ISNULL(SucursalGroupId, 0) AS NVARCHAR(20))';
        ELSE IF LOWER(@GroupBy) = 'zona'
            SET @groupKeyExpr = 'ISNULL(GrupoCode, '''')';
        ELSE IF LOWER(@GroupBy) = 'general'
            SET @groupKeyExpr = '''GENERAL''';
    END

    -- Expresión para ordenar por el id numérico del grupo (ascendente) cuando esté disponible
    DECLARE @groupOrderExpr NVARCHAR(200) = '';
    IF LOWER(@GroupBy) = 'sucursal' SET @groupOrderExpr = 'ISNULL(SucursalGroupId,0)';
    ELSE IF LOWER(@GroupBy) = 'vendedor' SET @groupOrderExpr = 'ISNULL(VendedorGroupId,0)';
    ELSE IF LOWER(@GroupBy) = 'modelo' SET @groupOrderExpr = 'ISNULL(ModeloGroupId,0)';
    ELSE IF LOWER(@GroupBy) = 'zona' SET @groupOrderExpr = 'ISNULL(GrupoCode, '''')';
    ELSE SET @groupOrderExpr = @groupKeyExpr; -- fallback

    DECLARE @colsForSum NVARCHAR(MAX) = '';
    DECLARE @weekColsForSum NVARCHAR(MAX) = '';
    DECLARE @yearColsForSum NVARCHAR(MAX) = '';
    DECLARE @detailSelect NVARCHAR(400) = '';
    DECLARE @detailGroup NVARCHAR(200) = '';
    DECLARE @detailOrder NVARCHAR(200) = '';

    -- Expresión legible para mostrar el agrupador en la matriz
    DECLARE @displayExpr NVARCHAR(200) = '';
    DECLARE @modeloExtraSelect NVARCHAR(400) = '';
    DECLARE @modeloExtraGroupBy NVARCHAR(400) = '';
    IF LOWER(@GroupBy) = 'sucursal' SET @displayExpr = @sucursalExpr;
    ELSE IF LOWER(@GroupBy) = 'vendedor' SET @displayExpr = @vendedorExpr;
    ELSE IF LOWER(@GroupBy) = 'modelo' SET @displayExpr = @modeloExpr;
    ELSE IF LOWER(@GroupBy) = 'zona' SET @displayExpr = 'GrupoCode';
    ELSE SET @displayExpr = '''GENERAL''';

    -- No agregar atributos extra del producto para GroupBy='modelo': solo mostrar Nombre del modelo
    SET @modeloExtraSelect = '';
    SET @modeloExtraGroupBy = '';

        
    -- Generar periodos completos y hacer LEFT JOIN con agregados para que se muestren incluso periodos sin ventas
    IF LOWER(@PeriodLevel) = 'diario'
    BEGIN
    -- Matriz por días si se solicita o si se pide detallado; en modo resumen no pivotear por días para sucursal/vendedor/modelo
    IF @IncludeMatrix = 1 OR LOWER(ISNULL(@ReportType,'resumen')) = 'detallado' OR (LOWER(ISNULL(@ReportType,'resumen')) = 'resumen' AND (LOWER(@GroupBy) IN ('zona')))
        BEGIN
            -- Generar columnas dinámicas por cada fecha en el rango StartDate..EndDate (incluye días sin ventas)
            DECLARE @cols NVARCHAR(MAX) = '';
            DECLARE @pivotDaysCount INT = DATEDIFF(day, @StartDate, @EndDate) + 1;
            DECLARE @i INT = 0;
            DECLARE @d VARCHAR(10);
            SET @colsForSum = '';
            WHILE @i < @pivotDaysCount
            BEGIN
                SET @d = CONVERT(varchar(10), DATEADD(day, @i, @StartDate), 23);
                IF LEN(@cols) = 0
                    SET @cols = QUOTENAME(@d);
                ELSE
                    SET @cols = @cols + ',' + QUOTENAME(@d);

                IF LEN(@colsForSum) = 0
                    SET @colsForSum = 'ISNULL(' + QUOTENAME(@d) + ',0)';
                ELSE
                    SET @colsForSum = @colsForSum + ' + ISNULL(' + QUOTENAME(@d) + ',0)';

                SET @i = @i + 1;
            END

            -- Preparar dato base: GroupLabel, DayLabel (fecha), Monto
            SET @sql = N'SELECT ' + @groupKeyExpr + ' AS GroupKey, ' + @displayExpr + ' AS GroupLabel' + ISNULL(@modeloExtraSelect, '') + ', CONVERT(varchar(10), DateCalc, 23) AS DayLabel, SUM(Total_USD) AS Monto '
                     + N'INTO #PivotData FROM #Source WHERE DateCalc BETWEEN CONVERT(date, ''' + CONVERT(varchar(10), @StartDate, 23) + ''') AND CONVERT(date, ''' + CONVERT(varchar(10), @EndDate, 23) + ''') '
                     + N'GROUP BY ' + @groupKeyExpr + ', ' + @displayExpr + ', CONVERT(varchar(10), DateCalc, 23)' + ISNULL(@modeloExtraGroupBy, '') + N'; '
                     + N'SELECT GroupKey, GroupLabel' + CASE WHEN LEN(ISNULL(@modeloExtraSelect,''))>0 THEN N', Marca, Serie, Color, Segmento' ELSE N'' END + N', ' + ISNULL(@cols, '''''') + N', (' + ISNULL(@colsForSum, '0') + N') AS [Total] FROM (SELECT GroupKey, GroupLabel' + CASE WHEN LEN(ISNULL(@modeloExtraSelect,''))>0 THEN N', DayLabel, Monto, Marca, Serie, Color, Segmento' ELSE N', DayLabel, Monto' END + N' FROM #PivotData) AS src '
                     + N'PIVOT (SUM(Monto) FOR DayLabel IN (' + ISNULL(@cols, '''''') + ')) AS pvt ORDER BY TRY_CAST(pvt.GroupKey AS INT), pvt.GroupLabel;';

            EXEC sp_executesql @sql;
            RETURN;
        END
        -- Si se solicita detallado: devolver filas de ventas (por fecha) y total por grupo
        IF LOWER(ISNULL(@ReportType,'resumen')) = 'detallado'
        BEGIN
            -- variables de detalle ya declaradas más arriba

            IF LOWER(@GroupBy) = 'sucursal'
            BEGIN
                SET @detailSelect = @sucursalExpr + ' AS Sucursal, CONVERT(varchar(10), DateCalc, 23) AS Fecha, Total_USD AS [Total Ventas]';
                SET @detailGroup = @sucursalExpr;
                SET @detailOrder = @sucursalExpr + ', DateCalc';
            END
            ELSE IF LOWER(@GroupBy) = 'vendedor'
            BEGIN
                SET @detailSelect = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50))) AS Vendedor, CONVERT(varchar(10), DateCalc, 23) AS Fecha, ISNULL(Total_USD,0) AS [Total Ventas]';
                SET @detailGroup = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
                SET @detailOrder = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))' + ', DateCalc';
            END
            ELSE IF LOWER(@GroupBy) = 'modelo'
            BEGIN
                SET @detailSelect = @modeloExpr + ' AS Modelo, CONVERT(varchar(10), DateCalc, 23) AS Fecha, Total_USD AS [Total Ventas]';
                SET @detailGroup = @modeloExpr;
                SET @detailOrder = @modeloExpr + ', DateCalc';
            END
            ELSE IF LOWER(@GroupBy) = 'zona'
            BEGIN
                SET @detailSelect = 'GrupoCode AS Zona, CONVERT(varchar(10), DateCalc, 23) AS Fecha, Total_USD AS [Total Ventas]';
                SET @detailGroup = 'GrupoCode';
                SET @detailOrder = 'GrupoCode, DateCalc';
            END
            ELSE
            BEGIN
                -- general: mostrar detalle completo como fallback
                SET @detailSelect = 'GrupoCode AS Zona, ' + @sucursalExpr + ' AS Sucursal, ' + @vendedorExpr + ' AS Vendedor, ' + @modeloExpr + ' AS Modelo, CONVERT(varchar(10), DateCalc, 23) AS Fecha, Total_USD AS [Total Ventas]';
                SET @detailGroup = @groupExpr;
                SET @detailOrder = 'GrupoCode, ' + @sucursalExpr + ', DateCalc';
            END

            SET @sql = N'SELECT ' + @detailSelect + ' FROM #Source ORDER BY ' + @detailOrder + ';' + CHAR(13) + CHAR(10)
                     + N'SELECT ' + @detailGroup + ' AS GroupLabel, SUM(Total_USD) AS [Total Ventas] FROM #Source GROUP BY ' + @detailGroup + ' ORDER BY ' + @detailGroup + ';';

            EXEC sp_executesql @sql;
            RETURN;
        END

        -- Si se pide resumen: devolver por grupo la cantidad de días en el rango y el total
        IF LOWER(ISNULL(@ReportType,'resumen')) = 'resumen'
        BEGIN
            -- Para resumen en modo diario solo devolver la cantidad de días (Periods) y sumar Monto.
            DECLARE @periodsCountLocal INT = DATEDIFF(day, @StartDate, @EndDate) + 1;
            IF LOWER(@GroupBy) = 'modelo'
            BEGIN
                -- Resumen para modelo: calcular id incremental usando MIN(ModeloGroupId) y mostrar sólo GroupLabel y Monto
                SET @sql = N'SELECT * FROM (
                    SELECT CAST(ISNULL(MIN(ModeloGroupId),0) AS NVARCHAR(20)) AS GroupKey,
                           MAX(ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))) AS GroupLabel,
                           ' + CAST(@periodsCountLocal AS NVARCHAR(10)) + N' AS Periods,
                           ISNULL(SUM(Total_USD),0) AS Monto
                    FROM #Source
                    GROUP BY ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))
                ) AS t
                ORDER BY TRY_CAST(t.GroupKey AS INT);';
            END
            ELSE
            BEGIN
                -- Resumen genérico: agrupar por la etiqueta legible y obtener un GroupKey numérico mínimo para orden
                DECLARE @minGroupCol NVARCHAR(200) = '0';
                IF LOWER(@GroupBy) = 'sucursal' SET @minGroupCol = 'ISNULL(SucursalGroupId,0)';
                ELSE IF LOWER(@GroupBy) = 'vendedor' SET @minGroupCol = 'ISNULL(VendedorGroupId,0)';
                ELSE SET @minGroupCol = '0';

                SET @sql = N'SELECT * FROM (
                    SELECT CAST(ISNULL(MIN(' + @minGroupCol + '),0) AS NVARCHAR(20)) AS GroupKey,
                           MAX(' + @displayExpr + N') AS GroupLabel,
                           ' + CAST(@periodsCountLocal AS NVARCHAR(10)) + N' AS Periods,
                           ISNULL(SUM(Total_USD),0) AS Monto
                    FROM #Source
                    GROUP BY ' + @displayExpr + N'
                ) AS t
                ORDER BY TRY_CAST(t.GroupKey AS INT);';
            END
            EXEC sp_executesql @sql;
            RETURN;
        END

        -- Modo detallado: usar Numbers CTE para devolver fila por día
        DECLARE @daysCount INT = DATEDIFF(day, @StartDate, @EndDate) + 1;
        SET @sql = N'
        WITH Numbers AS (
            SELECT TOP (' + CAST(@daysCount AS NVARCHAR(10)) + ') ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) - 1 AS n
            FROM sys.all_objects
        ), Periods AS (
            SELECT DATEADD(day, n, CONVERT(date, ''' + CONVERT(varchar(10), @StartDate, 23) + ''')) AS PeriodDate
            FROM Numbers
        ), Groups AS (
            -- Proyectar explícitamente GroupKey y GroupLabel para que las uniones posteriores puedan referenciarlas sin ambigüedad
            SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel
            FROM #Source
            GROUP BY ' + @groupKeyExpr + '
        ), Agg AS (
            SELECT ' + @groupKeyExpr + ' AS GroupKey, CONVERT(date, DateCalc) AS PeriodDate, SUM(Total_USD) AS Monto
            FROM #Source
            GROUP BY ' + @groupKeyExpr + ', CONVERT(date, DateCalc)
        )
        SELECT g.GroupKey, g.GroupLabel, CONVERT(varchar(10), p.PeriodDate, 23) AS Period, ISNULL(a.Monto,0) AS Monto
        FROM Groups g
        CROSS JOIN Periods p
        LEFT JOIN Agg a ON a.GroupKey = g.GroupKey AND a.PeriodDate = p.PeriodDate
        ORDER BY g.GroupLabel, p.PeriodDate;
        ';

    EXEC sp_executesql @sql;
        RETURN;
    END

    IF LOWER(@PeriodLevel) = 'semanal'
    BEGIN
    IF @IncludeMatrix = 1 OR LOWER(ISNULL(@ReportType,'resumen')) = 'detallado'
        BEGIN
                -- Generar columnas W1..Wn entre start and end o si se solicita resumen por agrupador
                DECLARE @weekCols NVARCHAR(MAX) = '';
                DECLARE @weekPivotCount INT = DATEDIFF(week, @StartDate, @EndDate) + 1;
                DECLARE @wi INT = 0;
                DECLARE @wkLabel VARCHAR(20);
                SET @weekColsForSum = '';
                WHILE @wi < @weekPivotCount
                BEGIN
                    SET @wkLabel = 'W' + CAST(DATEPART(week, DATEADD(week, @wi, @StartDate)) AS VARCHAR(5));
                    IF LEN(@weekCols) = 0
                        SET @weekCols = QUOTENAME(@wkLabel);
                    ELSE
                        SET @weekCols = @weekCols + ',' + QUOTENAME(@wkLabel);

                    IF LEN(@weekColsForSum) = 0
                        SET @weekColsForSum = 'ISNULL(' + QUOTENAME(@wkLabel) + ',0)';
                    ELSE
                        SET @weekColsForSum = @weekColsForSum + ' + ISNULL(' + QUOTENAME(@wkLabel) + ',0)';

                    SET @wi = @wi + 1;
                END

                SET @sql = N'SELECT ' + @groupKeyExpr + ' AS GroupKey, ' + @displayExpr + ' AS GroupLabel' + ISNULL(@modeloExtraSelect, '') + N', ''W'' + CAST(DATEPART(week, DateCalc) AS VARCHAR(5)) AS WeekLabel, SUM(Total_USD) AS Monto '
                         + N'INTO #PivotData FROM #Source GROUP BY ' + @groupKeyExpr + ', ' + @displayExpr + ', DATEPART(week, DateCalc)' + ISNULL(@modeloExtraGroupBy, '') + N'; '
                         + N'SELECT GroupKey, GroupLabel' + CASE WHEN LEN(ISNULL(@modeloExtraSelect,''))>0 THEN N', Marca, Serie, Color, Segmento' ELSE N'' END + N', ' + ISNULL(@weekCols, '''''') + N', (' + ISNULL(@weekColsForSum, '0') + N') AS [Total] FROM (SELECT GroupKey, GroupLabel' + CASE WHEN LEN(ISNULL(@modeloExtraSelect,''))>0 THEN N', WeekLabel, Monto, Marca, Serie, Color, Segmento' ELSE N', WeekLabel, Monto' END + N' FROM #PivotData) AS src '
                         + N'PIVOT (SUM(Monto) FOR WeekLabel IN (' + ISNULL(@weekCols, '''''') + ')) AS pvt ORDER BY TRY_CAST(pvt.GroupKey AS INT), pvt.GroupLabel;';

                EXEC sp_executesql @sql;
                RETURN;
            END
        -- Si se pide resumen: devolver por grupo la cantidad de semanas en el rango y el total
        IF LOWER(ISNULL(@ReportType,'resumen')) = 'resumen'
        BEGIN
            DECLARE @periodsWeeks INT = DATEDIFF(week, @StartDate, @EndDate) + 1;
            IF LOWER(@GroupBy) = 'modelo'
            BEGIN
                -- Resumen para modelo: sólo nombre de modelo único y id ascendente
                SET @sql = N'SELECT * FROM (
                    SELECT CAST(ISNULL(MIN(ModeloGroupId),0) AS NVARCHAR(20)) AS GroupKey,
                           MAX(ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))) AS GroupLabel,
                           ' + CAST(@periodsWeeks AS NVARCHAR(10)) + N' AS Periods,
                           ISNULL(SUM(Total_USD),0) AS Monto
                    FROM #Source
                    GROUP BY ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))
                ) AS t
                ORDER BY TRY_CAST(t.GroupKey AS INT);';
            END
            ELSE
            BEGIN
                SET @sql = N'SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel, ' + CAST(@periodsWeeks AS NVARCHAR(10)) + ' AS Periods, ISNULL(SUM(Total_USD),0) AS Monto '
                         + N'SELECT * FROM (SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel, ' + CAST(@periodsWeeks AS NVARCHAR(10)) + N' AS Periods, ISNULL(SUM(Total_USD),0) AS Monto FROM #Source GROUP BY ' + @groupKeyExpr + N') AS t ORDER BY TRY_CAST(t.GroupKey AS INT);';
            END
            EXEC sp_executesql @sql;
            RETURN;
        END

        -- Detallado por semana: devolver filas de venta con su semana y total por grupo
        IF LOWER(ISNULL(@ReportType,'resumen')) = 'detallado'
        BEGIN
            -- variables de detalle ya declaradas más arriba

            IF LOWER(@GroupBy) = 'sucursal'
            BEGIN
                SET @detailSelect = @sucursalExpr + ' AS Sucursal, CONCAT(''W'', DATEPART(week, DateCalc)) AS Semana, Total_USD AS [Total Ventas]';
                SET @detailGroup = @sucursalExpr;
                SET @detailOrder = @sucursalExpr + ', DateCalc';
            END
            ELSE IF LOWER(@GroupBy) = 'vendedor'
            BEGIN
                SET @detailSelect = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50))) AS Vendedor, CONCAT(''W'', DATEPART(week, DateCalc)) AS Semana, ISNULL(Total_USD,0) AS [Total Ventas]';
                SET @detailGroup = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))';
                SET @detailOrder = 'ISNULL(VendedorNombre, CAST(VendedorId AS NVARCHAR(50)))' + ', DateCalc';
            END
            ELSE IF LOWER(@GroupBy) = 'modelo'
            BEGIN
                SET @detailSelect = @modeloExpr + ' AS Modelo, CONCAT(''W'', DATEPART(week, DateCalc)) AS Semana, Total_USD AS [Total Ventas]';
                SET @detailGroup = @modeloExpr;
                SET @detailOrder = @modeloExpr + ', DateCalc';
            END
            ELSE IF LOWER(@GroupBy) = 'zona'
            BEGIN
                SET @detailSelect = 'GrupoCode AS Zona, CONCAT(''W'', DATEPART(week, DateCalc)) AS Semana, Total_USD AS [Total Ventas]';
                SET @detailGroup = 'GrupoCode';
                SET @detailOrder = 'GrupoCode, DateCalc';
            END
            ELSE
            BEGIN
                SET @detailSelect = 'GrupoCode AS Zona, ' + @sucursalExpr + ' AS Sucursal, ' + @vendedorExpr + ' AS Vendedor, ' + @modeloExpr + ' AS Modelo, CONCAT(''W'', DATEPART(week, DateCalc)) AS Semana, Total_USD AS [Total Ventas]';
                SET @detailGroup = @groupExpr;
                SET @detailOrder = 'GrupoCode, ' + @sucursalExpr + ', DateCalc';
            END

            SET @sql = N'SELECT ' + @detailSelect + ' FROM #Source ORDER BY ' + @detailOrder + ';' + CHAR(13) + CHAR(10)
                     + N'SELECT ' + @detailGroup + ' AS GroupLabel, SUM(Total_USD) AS [Total Ventas] FROM #Source GROUP BY ' + @detailGroup + ' ORDER BY ' + @detailGroup + ';';

            EXEC sp_executesql @sql;
            RETURN;
        END

        -- Generar semanas: usaremos fecha de inicio de semana (lunes) como PeriodDate
        DECLARE @weeksCount INT = DATEDIFF(week, @StartDate, @EndDate) + 1;
        SET @sql = N'
        WITH Numbers AS (
            SELECT TOP (' + CAST(@weeksCount AS NVARCHAR(10)) + ') ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) - 1 AS n
            FROM sys.all_objects
        ), Periods AS (
            SELECT DATEADD(week, n, CONVERT(date, ''' + CONVERT(varchar(10), @StartDate, 23) + ''')) AS PeriodDate
            FROM Numbers
        ), Groups AS (
            -- Proyectar explícitamente GroupKey y GroupLabel para que las uniones posteriores puedan referenciarlas sin ambigüedad
            SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel
            FROM #Source
            GROUP BY ' + @groupKeyExpr + '
        ), Agg AS (
            SELECT ' + @groupKeyExpr + ' AS GroupKey, DATEADD(week, DATEDIFF(week, 0, DateCalc), 0) AS PeriodDate, SUM(Total_USD) AS Monto
            FROM #Source
            GROUP BY ' + @groupKeyExpr + ', DATEADD(week, DATEDIFF(week, 0, DateCalc), 0)
        )
    SELECT g.GroupKey, g.GroupLabel, CONCAT(''W'', DATEPART(week, p.PeriodDate)) AS Period, ISNULL(a.Monto,0) AS Monto
        FROM Groups g
        CROSS JOIN Periods p
        LEFT JOIN Agg a ON a.GroupKey = g.GroupKey AND a.PeriodDate = DATEADD(week, DATEDIFF(week, 0, p.PeriodDate), 0)
        ORDER BY g.GroupLabel, p.PeriodDate;
        ';

    EXEC sp_executesql @sql;
        RETURN;
    END

    IF LOWER(@PeriodLevel) = 'anual' OR LOWER(@PeriodLevel) = 'año'
    BEGIN
        IF @IncludeMatrix = 1
        BEGIN
            DECLARE @yearCols NVARCHAR(MAX);
            -- Generar lista de años y preparar columnas dinámicas
            SELECT
                @yearCols = STRING_AGG(QUOTENAME(CAST(yr AS VARCHAR(10))), ','),
                @yearColsForSum = STRING_AGG('ISNULL(' + QUOTENAME(CAST(yr AS VARCHAR(10))) + ',0)', ' + ')
            FROM (SELECT DISTINCT ANIO AS yr FROM #Source) AS yrs;

            SET @sql = N'SELECT ' + @groupKeyExpr + ' AS GroupKey, ' + @displayExpr + ' AS GroupLabel, CAST(ANIO AS VARCHAR(10)) AS YearLabel, SUM(Total_USD) AS Monto '
                     + N'INTO #PivotData FROM #Source GROUP BY ' + @groupKeyExpr + ', ' + @displayExpr + ', ANIO; '
                     + N'SELECT GroupKey, GroupLabel, ' + @yearCols + ', (' + ISNULL(@yearColsForSum, '0') + ') AS [Total] FROM (SELECT GroupKey, GroupLabel, YearLabel, Monto FROM #PivotData) AS src '
                     + N'PIVOT (SUM(MONTO) FOR YearLabel IN (' + @yearCols + ')) AS pvt ORDER BY TRY_CAST(pvt.GroupKey AS INT), pvt.GroupLabel;';

            EXEC sp_executesql @sql;
            RETURN;
        END
        -- Detallado por año: filas de venta con ANIO y total por grupo
        IF LOWER(ISNULL(@ReportType,'resumen')) = 'detallado'
        BEGIN
            -- variables de detalle ya declaradas más arriba

            IF LOWER(@GroupBy) = 'sucursal'
            BEGIN
                SET @detailSelect = @sucursalExpr + ' AS Sucursal, CAST(ANIO AS NVARCHAR(10)) AS Anio, Total_USD AS [Total Ventas]';
                SET @detailGroup = @sucursalExpr;
                SET @detailOrder = @sucursalExpr + ', ANIO';
            END
            ELSE IF LOWER(@GroupBy) = 'vendedor'
            BEGIN
                SET @detailSelect = @vendedorExpr + ' AS Vendedor, CAST(ANIO AS NVARCHAR(10)) AS Anio, Total_USD AS [Total Ventas]';
                SET @detailGroup = @vendedorExpr;
                SET @detailOrder = @vendedorExpr + ', ANIO';
            END
            ELSE IF LOWER(@GroupBy) = 'modelo'
            BEGIN
                SET @detailSelect = @modeloExpr + ' AS Modelo, CAST(ANIO AS NVARCHAR(10)) AS Anio, Total_USD AS [Total Ventas]';
                SET @detailGroup = @modeloExpr;
                SET @detailOrder = @modeloExpr + ', ANIO';
            END
            ELSE IF LOWER(@GroupBy) = 'zona'
            BEGIN
                SET @detailSelect = 'GrupoCode AS Zona, CAST(ANIO AS NVARCHAR(10)) AS Anio, Total_USD AS [Total Ventas]';
                SET @detailGroup = 'GrupoCode';
                SET @detailOrder = 'GrupoCode, ANIO';
            END
            ELSE
            BEGIN
                SET @detailSelect = 'GrupoCode AS Zona, ' + @sucursalExpr + ' AS Sucursal, ' + @vendedorExpr + ' AS Vendedor, ' + @modeloExpr + ' AS Modelo, CAST(ANIO AS NVARCHAR(10)) AS Anio, Total_USD AS [Total Ventas]';
                SET @detailGroup = @groupExpr;
                SET @detailOrder = 'GrupoCode, ' + @sucursalExpr + ', ANIO';
            END

            SET @sql = N'SELECT ' + @detailSelect + ' FROM #Source ORDER BY ' + @detailOrder + ';' + CHAR(13) + CHAR(10)
                     + N'SELECT ' + @detailGroup + ' AS GroupLabel, SUM(Total_USD) AS [Total Ventas] FROM #Source GROUP BY ' + @detailGroup + ' ORDER BY ' + @detailGroup + ';';

            EXEC sp_executesql @sql;
            RETURN;
        END

        DECLARE @startYear INT = YEAR(@StartDate);
        DECLARE @endYear INT = YEAR(@EndDate);
        DECLARE @yearsCount INT = @endYear - @startYear + 1;
        SET @sql = N'
        WITH Numbers AS (
            SELECT TOP (' + CAST(@yearsCount AS NVARCHAR(10)) + ') ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) - 1 AS n
            FROM sys.all_objects
        ), Periods AS (
            SELECT DATEADD(year, n, CONVERT(date, ''' + CONVERT(varchar(10), DATEFROMPARTS(@startYear,1,1), 23) + ''')) AS PeriodDate
            FROM Numbers
        ), Groups AS (
            -- Proyectar explícitamente GroupKey y GroupLabel para que las uniones posteriores puedan referenciarlas sin ambigüedad
            SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel
            FROM #Source
            GROUP BY ' + @groupKeyExpr + '
        ), Agg AS (
            SELECT ' + @groupKeyExpr + ' AS GroupKey, ANIO AS PeriodYear, SUM(Total_USD) AS Monto
            FROM #Source
            GROUP BY ' + @groupKeyExpr + ', ANIO
        )
        SELECT g.GroupKey, g.GroupLabel, CAST(DATEPART(year, p.PeriodDate) AS varchar(10)) AS Period, ISNULL(a.Monto,0) AS Monto
        FROM Groups g
        CROSS JOIN Periods p
        LEFT JOIN Agg a ON a.GroupKey = g.GroupKey AND a.PeriodYear = DATEPART(year, p.PeriodDate)
        ORDER BY g.GroupLabel, p.PeriodDate;
        ';

        EXEC sp_executesql @sql;
        RETURN;
    END

    -- Modo mensual por defecto: si se solicita matriz o resumen por agrupador, pivotar por meses (YYYY-MM)
    IF @IncludeMatrix = 1 OR LOWER(ISNULL(@ReportType,'resumen')) = 'detallado' OR (LOWER(ISNULL(@ReportType,'resumen')) = 'resumen' AND (LOWER(@GroupBy) IN ('zona','sucursal','vendedor','modelo')))
    BEGIN
        DECLARE @monthCols NVARCHAR(MAX) = '';
        DECLARE @monthColsForSum NVARCHAR(MAX) = '';
    DECLARE @monthsCount INT = (YEAR(@EndDate) - YEAR(@StartDate)) * 12 + (MONTH(@EndDate) - MONTH(@StartDate)) + 1;
        DECLARE @mi INT = 0;
        DECLARE @mLabel VARCHAR(7);
        WHILE @mi < @monthsCount
        BEGIN
            SET @mLabel = CONVERT(varchar(7), DATEADD(month, @mi, @StartDate), 23); -- 'YYYY-MM'
            IF LEN(@monthCols) = 0
                SET @monthCols = QUOTENAME(@mLabel);
            ELSE
                SET @monthCols = @monthCols + ',' + QUOTENAME(@mLabel);

            IF LEN(@monthColsForSum) = 0
                SET @monthColsForSum = 'ISNULL(' + QUOTENAME(@mLabel) + ',0)';
            ELSE
                SET @monthColsForSum = @monthColsForSum + ' + ISNULL(' + QUOTENAME(@mLabel) + ',0)';

            SET @mi = @mi + 1;
        END
    SET @sql = N'SELECT ' + @groupKeyExpr + ' AS GroupKey, ' + @displayExpr + ' AS GroupLabel' + ISNULL(@modeloExtraSelect, '') + N', CONVERT(varchar(7), DateCalc, 23) AS MonthLabel, SUM(Total_USD) AS Monto '
     + N'INTO #PivotData FROM #Source GROUP BY ' + @groupKeyExpr + ', ' + @displayExpr + ', CONVERT(varchar(7), DateCalc, 23)' + ISNULL(@modeloExtraGroupBy, '') + N'; '
         + N'SELECT GroupKey, GroupLabel' + CASE WHEN LEN(ISNULL(@modeloExtraSelect,''))>0 THEN N', Marca, Serie, Color, Segmento' ELSE N'' END + N', ' + ISNULL(@monthCols, '''''') + N', (' + ISNULL(@monthColsForSum, '0') + N') AS [Total] '
         + N'FROM (SELECT GroupKey, GroupLabel' + CASE WHEN LEN(ISNULL(@modeloExtraSelect,''))>0 THEN N', MonthLabel, Monto, Marca, Serie, Color, Segmento' ELSE N', MonthLabel, Monto' END + N' FROM #PivotData) AS src '
         + N'PIVOT (SUM(Monto) FOR MonthLabel IN (' + ISNULL(@monthCols, '''''') + ')) AS pvt ORDER BY TRY_CAST(pvt.GroupKey AS INT), pvt.GroupLabel;';

        -- Si se pide resumen: devolver por grupo la cantidad de meses en el rango y el total
        IF LOWER(ISNULL(@ReportType,'resumen')) = 'resumen'
        BEGIN
            DECLARE @periodsMonths INT = @monthsCount;
            IF LOWER(@GroupBy) = 'modelo'
            BEGIN
                -- Resumen mensual para modelo: sólo nombre de modelo único y id ascendente
                SET @sql = N'SELECT * FROM (
                    SELECT CAST(ISNULL(MIN(ModeloGroupId),0) AS NVARCHAR(20)) AS GroupKey,
                           MAX(ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))) AS GroupLabel,
                           ' + CAST(@periodsMonths AS NVARCHAR(10)) + N' AS Periods,
                           ISNULL(SUM(Total_USD),0) AS Monto
                    FROM #Source
                    GROUP BY ISNULL(ModeloNombre, CAST(ProductoId AS NVARCHAR(50)))
                ) AS t
                ORDER BY TRY_CAST(t.GroupKey AS INT);';
            END
            ELSE
            BEGIN
                SET @sql = N'SELECT * FROM (SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel, ' + CAST(@periodsMonths AS NVARCHAR(10)) + N' AS Periods, ISNULL(SUM(Total_USD),0) AS Monto FROM #Source GROUP BY ' + @groupKeyExpr + N') AS t ORDER BY TRY_CAST(t.GroupKey AS INT);';
            END
            EXEC sp_executesql @sql;
            RETURN;
        END

        -- Modo detallado mensual: pivot por meses (ya construido arriba)
        EXEC sp_executesql @sql;
        RETURN;
    END
    ELSE
    BEGIN
    SET @sql = N'
    SELECT * FROM (
        SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel, CAST(MES AS NVARCHAR(5)) AS Period, SUM(Total_USD) AS Monto
        FROM #Source
        GROUP BY ' + @groupKeyExpr + ', MES
        UNION ALL
        SELECT ' + @groupKeyExpr + ' AS GroupKey, MAX(' + @displayExpr + ') AS GroupLabel, ''Total'' AS Period, SUM(Total_USD) AS Monto
        FROM #Source
        GROUP BY ' + @groupKeyExpr + '
    ) AS t
    ORDER BY TRY_CAST(t.GroupKey AS INT), Period;
    ';

        EXEC sp_executesql @sql;
    END
END

GO
