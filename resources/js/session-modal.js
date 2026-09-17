  document.addEventListener('livewire:init', () => {
        Livewire.interceptRequest(({ onError }) => {
            onError(({ response, preventDefault }) => {
                if (response.status !== 419) {
                    return;
                }

                preventDefault();
                Flux.modal('global-token-expiration').show();
            });
        });
    });