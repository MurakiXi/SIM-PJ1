document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('payment_method');
    const preview = document.getElementById('payment_method_preview');

    if (!select || !preview) return;

    const labelMap = {
        '': '選択してください',
        convenience_store: 'コンビニ払い',
        card: 'カード支払い',
    };

    const render = () => {
        preview.textContent = labelMap[select.value] ?? '選択してください';
    };

    select.addEventListener('change', render);
    render();
});
