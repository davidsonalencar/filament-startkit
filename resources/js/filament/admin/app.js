import './bootstrap';

document.addEventListener('livewire:init', () => {
    Livewire.on('open-url', (event) => {
        window.open(event.url, '_blank');
    });

    // if (window.Echo) {
    //     console.log('Echo is ready, subscribing to channel: App.Models.User.' + window.FilamentAuth.id);
    //     window.Echo.private(`App.Models.User.${window.FilamentAuth.id}`)
    //         .notification((notification) => {
    //             console.log('New notification received via broadcast:', notification);
    //         });
    // } else {
    //     console.error('Laravel Echo is not defined. Check your bootstrap.js and echo.js configuration.');
    // }
});
