import './bootstrap';

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');

    if (!toggle) {
        return;
    }

    const inputId = toggle.getAttribute('aria-controls');
    const input = inputId
        ? document.getElementById(inputId)
        : toggle.parentElement?.querySelector('[data-password-toggle-input]');

    if (!(input instanceof HTMLInputElement) || input.type === 'hidden') {
        return;
    }

    const shouldShow = input.type === 'password';
    input.type = shouldShow ? 'text' : 'password';
    toggle.textContent = shouldShow ? 'Hide password' : 'Show password';
    toggle.setAttribute('aria-pressed', String(shouldShow));
    toggle.setAttribute(
        'aria-label',
        shouldShow ? 'Hide password' : 'Show password'
    );
});