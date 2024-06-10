const hamBurger = document.querySelector(".toggle-btn");
const sidebar = document.querySelector("#sidebar");
const mainContent = document.querySelector(".main");

hamBurger.addEventListener("click", function() {
    sidebar.classList.toggle("expand");
    mainContent.classList.toggle("expand");
});
