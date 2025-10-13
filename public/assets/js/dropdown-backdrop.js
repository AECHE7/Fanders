document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.getElementById('navbarDropdown');
    if (!dropdownToggle) return;

    const dropdownMenu = dropdownToggle.nextElementSibling;
    if (!dropdownMenu) return;

    // Create backdrop element
    let backdrop = document.getElementById('dropdownBackdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'dropdownBackdrop';
        backdrop.style.position = 'right';
        backdrop.style.top = '0';
        backdrop.style.left = '0';
        backdrop.style.width = '100vw';
        backdrop.style.height = '100vh';
        backdrop.style.backgroundColor = 'rgba(0,0,0,0.1)'; // semi-transparent backdrop
        backdrop.style.zIndex = '1040'; // less than dropdown z-index 1050
        backdrop.style.display = 'none';
        document.body.appendChild(backdrop);
    }

    function showBackdrop() {
        backdrop.style.display = 'block';
    }

    function hideBackdrop() {
        backdrop.style.display = 'none';
    }

    // Show backdrop when dropdown is shown
    dropdownToggle.addEventListener('click', function() {
        if (dropdownMenu.classList.contains('show')) {
            hideBackdrop();
        } else {
            showBackdrop();
        }
    });

    // Hide dropdown and backdrop when clicking on backdrop
    backdrop.addEventListener('click', function() {
        const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
        if (bsDropdown) {
            bsDropdown.hide();
        }
        hideBackdrop();
    });
});
