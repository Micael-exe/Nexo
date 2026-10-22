// Alpine é disponibilizado pelo Livewire. Não inicializar uma segunda instância.
window.CanalEmDia = {
    locate(wire, state) {
        if (!navigator.geolocation) {
            state.locationMessage = 'Seu navegador não oferece localização. Informe o endereço e a referência abaixo para continuar.';
            return;
        }
        state.locating = true;
        state.locationMessage = 'Buscando sua localização. Você também pode preencher o endereço manualmente.';
        navigator.geolocation.getCurrentPosition(async (position) => {
            try {
                await Promise.all([
                    wire.set('latitude', position.coords.latitude.toFixed(7), false),
                    wire.set('longitude', position.coords.longitude.toFixed(7), false),
                ]);
                state.locationMessage = 'Coordenadas adicionadas. Complete também o endereço e a referência para identificar o trecho.';
            } catch {
                state.locationMessage = 'Não foi possível adicionar as coordenadas. O endereço e a referência são suficientes para continuar.';
            } finally { state.locating = false; }
        }, (error) => {
            state.locating = false;
            state.locationMessage = error.code === 1
                ? 'A permissão de localização não foi concedida. Tudo bem: informe o endereço e a referência abaixo.'
                : 'Não foi possível obter a localização agora. Informe o endereço e a referência abaixo para continuar.';
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 });
    },
    async copyCredential(text, state, element) {
        try {
            if (!navigator.clipboard?.writeText) throw new Error('Clipboard indisponível');
            await navigator.clipboard.writeText(text);
            state.copyMessage = 'Chave copiada. Guarde-a em um lugar seguro.';
        } catch {
            if (element) {
                const selection = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(element);
                selection?.removeAllRanges();
                selection?.addRange(range);
                element.focus();
            }
            state.copyMessage = 'A cópia automática não está disponível. Selecione a chave e use Copiar no seu dispositivo.';
        }
    },
};
document.addEventListener('livewire:init', () => {
    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 0 || status === 419 || status >= 500) {
                preventDefault();
                window.dispatchEvent(new CustomEvent('request-failed', { detail: { expired: status === 419 } }));
            }
        });
    });
});
