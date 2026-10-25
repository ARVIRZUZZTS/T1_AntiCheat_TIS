{{-- Datos de prueba para reportes - quitar con localStorage: reportes_pruebas=off o sin pruebas_reporte --}}
<script>
if (localStorage.getItem('reportes_pruebas') === null) {
    // Activado por defecto si app.debug o ?pruebas_reporte
}
function toggleReportesPrueba(on) {
    if (on) {
        localStorage.removeItem('reportes_pruebas');
    } else {
        localStorage.setItem('reportes_pruebas', 'off');
    }
    location.reload();
}
</script>
