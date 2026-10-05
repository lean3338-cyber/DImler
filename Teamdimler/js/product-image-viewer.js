const imageViewer = document.querySelector('#visor-imagen');

if (imageViewer instanceof HTMLDialogElement) {
    const enlargedImage = imageViewer.querySelector('.visor-imagen-foto');
    const closeButton = imageViewer.querySelector('.visor-imagen-cerrar');

    if (enlargedImage instanceof HTMLImageElement && closeButton instanceof HTMLButtonElement) {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('.producto-imagen-ampliar');
            if (!(trigger instanceof HTMLButtonElement)) {
                return;
            }

            enlargedImage.src = trigger.dataset.imagen;
            enlargedImage.alt = trigger.dataset.textoAlternativo || '';
            imageViewer.showModal();
            closeButton.focus();
        });

        closeButton.addEventListener('click', () => imageViewer.close());

        imageViewer.addEventListener('click', (event) => {
            if (event.target === imageViewer) {
                imageViewer.close();
            }
        });

        imageViewer.addEventListener('close', () => {
            enlargedImage.removeAttribute('src');
            enlargedImage.alt = '';
        });
    }
}
