import './bootstrap';
import './echo';

// NO importes Alpine. Livewire ya lo trae.

document.addEventListener('livewire:init', () => {
    // Lee el idExamen del DOM
    const idExamen = document.querySelector('[data-id-examen]')?.dataset.idExamen;

    if (!idExamen) {
        console.warn('No se encontró data-id-examen en el DOM');
        return;
    }

    console.log('Suscribiendo al canal examen.' + idExamen);

    window.Echo.channel(`examen.${idExamen}`)
        .listen('.AsistenciaRegistrada', (e) => {
            console.log('Evento recibido:', e);
            Livewire.dispatch('asistencia-registrada', { payload: e });
        });
});