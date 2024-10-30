
document.addEventListener('DOMContentLoaded', function() {
    const subjectList = document.querySelector('.subject-list');
    const dropdownMenu = document.querySelector('.list');

    subjectList.addEventListener('click', function(event) {
        event.preventDefault();
        dropdownMenu.classList.toggle('show');
        const expanded = dropdownMenu.classList.contains('show');
        subjectList.setAttribute('aria-expanded', expanded);
    });

    // Optional: Close the dropdown if clicked outside
    document.addEventListener('click', function(event) {
        if (!subjectList.contains(event.target) && !dropdownMenu.contains(event.target)) {
            dropdownMenu.classList.remove('show');
            subjectList.setAttribute('aria-expanded', 'false');
        }
    });
});

