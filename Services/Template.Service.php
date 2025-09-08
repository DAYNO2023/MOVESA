<?php
class ControllerTemplate {
    public function __construct() {
        $this->renderTemplate();
    }

    public function renderTemplate() {
        include __DIR__ . '/../Views/Template.php';  // Ajustamos la ruta a Template.php
    }
}

new ControllerTemplate();

?>
