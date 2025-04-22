function init() {
    const createListDialog = document.getElementById("createListDialog");
    const closeBtn = document.querySelector(".closeDialog");
    const newListBtn = document.getElementById("newList");
    const listForm = document.getElementById("listForm");
    newListBtn.addEventListener("click", () => {
        createListDialog.showModal();
        listForm.addEventListener("submit", createList);
    });
    closeBtn.addEventListener("click", () => createListDialog.close());
}
window.addEventListener("DOMContentLoaded", init);

// Skapa en lista
function createList(e) {
    e.preventDefault();
    const name = document.getElementById("listName").value.trim();
    if (!name) {
        alert("Du behöver namnge listan");
        return;
    }
    fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            name: name
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.listId) {
                window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + data.listId;
            } else {
                console.error("Error: List ID not received.");
            }
        })
        .catch(error => console.error("Error creating list:", error));
}
