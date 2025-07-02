const popup = document.getElementById("popup");
const showLink = document.getElementById("showPopup");
const closeBtn = document.getElementById("okay");

function toggleShow() {
  popup.classList.remove("hidden");
}
function toogleClose() {
  popup.classList.add("hidden");
}

showLink.addEventListener("click", function (e) {
  e.preventDefault();
  toggleShow();
});

closeBtn.addEventListener("click", toogleClose);
