
<?php
class LoginModel {
    public $id;
    public $nombre;
    public $rol;

    public function __construct($id, $nombre, $rol) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->rol = $rol;
    }
}
?>