/**
 * User Profile Controller (Read-Only Default View)
 * Handles Discord handle copy, toast confirmations, and public interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Check for success status parameter from profile editing
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('updated') === '1') {
        if (typeof window.showToast === 'function') {
            window.showToast('Profile and skills updated successfully!', 'success', 'Changes Saved');
        }
        urlParams.delete('updated');
        const cleanQuery = urlParams.toString() ? '?' + urlParams.toString() : '';
        const cleanUrl = window.location.pathname + cleanQuery + window.location.hash;
        window.history.replaceState({}, document.title, cleanUrl);
    }

    // 2. Helper: Copy Discord tag to clipboard with visual badge state
    function bindDiscordCopyButtons() {
        document.querySelectorAll('.social-link-copy').forEach(btn => {
            btn.onclick = async () => {
                const tag = btn.getAttribute('data-copy');
                if (!tag) return;
                try {
                    await navigator.clipboard.writeText(tag);
                    if (typeof window.showToast === 'function') {
                        window.showToast(`Discord handle "${tag}" copied to clipboard!`, 'success', 'Copied');
                    }
                } catch (err) {
                    const temp = document.createElement('textarea');
                    temp.value = tag;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    if (typeof window.showToast === 'function') {
                        window.showToast(`Discord handle "${tag}" copied!`, 'success', 'Copied');
                    }
                }
                const badge = btn.querySelector('.social-copy-badge');
                if (badge) {
                    const originalText = badge.textContent;
                    badge.textContent = '✓ Copied!';
                    badge.classList.add('badge-copied-active');
                    setTimeout(() => {
                        badge.textContent = originalText;
                        badge.classList.remove('badge-copied-active');
                    }, 2000);
                }
            };
        });
    }
    bindDiscordCopyButtons();

    // 3. Helper: Copy username chip if present
    const copyUsernameBtn = document.getElementById('btn-copy-username');
    if (copyUsernameBtn) {
        copyUsernameBtn.onclick = async () => {
            const handle = copyUsernameBtn.getAttribute('data-username');
            if (!handle) return;
            const textToCopy = '@' + handle;
            try {
                await navigator.clipboard.writeText(textToCopy);
                if (typeof window.showToast === 'function') {
                    window.showToast(`Username "${textToCopy}" copied!`, 'success', 'Copied');
                }
            } catch (e) {
                const temp = document.createElement('textarea');
                temp.value = textToCopy;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                if (typeof window.showToast === 'function') {
                    window.showToast(`Username "${textToCopy}" copied!`, 'success', 'Copied');
                }
            }
        };
    }
});
