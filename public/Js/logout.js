function init() {
    document.getElementById("logoutBtn").addEventListener("click", logout);
}
window.addEventListener("DOMContentLoaded", init);

// Logga ut
function logout() {
    localStorage.removeItem("userid");
    fetch("https://melab.lnu.se/~hh223ji/uppgift/public/logout", {
        method: "POST"
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/login.php";
            } else {
                console.error("Logout failed:", data.message);
            }
        })
        .catch(error => console.error("Error during logout:", error));
}
