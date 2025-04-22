function init() {
    saveUserId().then(() => {
        document.getElementById("usernameTag").textContent = localStorage.getItem("username");
        showList();
    });
}
window.addEventListener("DOMContentLoaded", init);

//Visa alla listor
async function showList() {
    try {
        const userId = localStorage.getItem("userid");
        if (!userId) {
            console.error("No user ID found in localStorage.");
            return;
        }
        const response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists");
        if (!response.ok) {
            throw new Error("HTTP error! Status:" + response.status);
        }
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (error) {
            throw new Error("Invalid JSON response: " + text);
        }
        const parentDiv = document.getElementById("listBox");
        parentDiv.innerHTML = "";
        if (!Array.isArray(data) || data.length === 0) {
            parentDiv.innerHTML = "<p id='errorMsg'>Du har inga listor, testa skapa nya</p>";
            return;
        }
        data.forEach(list => {
            const div = createListElement(list);
            parentDiv.appendChild(div);
        });
    } catch (error) {
        console.error("Error loading lists:", error);
    }
}

//Skapa element för ny lista
function createListElement(list) {
    const currentUser = localStorage.getItem("userid");

    const div = document.createElement("div");
    div.classList.add("list");

    const listInfo = document.createElement("div");
    listInfo.classList.add("listInfo");

    const name = document.createElement("p");
    name.textContent = list.name;
    name.classList.add("listName");

    const owner = document.createElement("p");
    owner.textContent = list.username;
    owner.classList.add("listOwner");

    listInfo.append(name, owner);
    div.appendChild(listInfo);

    const shareBtn = document.createElement("div");
    shareBtn.classList.add("shareBtn");
    shareBtn.innerHTML = "<p>Dela Lista</p>";
    shareBtn.addEventListener("click", (event) => {
        event.stopPropagation();
        openShareDialog(list.id);
    });

    if (list.ownerid === currentUser) {
        const trashDiv = document.createElement("div");
        trashDiv.classList.add("trashDiv");
        trashDiv.addEventListener("click", (event) => {
            event.stopPropagation();
            deleteList(list.id);

        });
        const trashImg = document.createElement("img");
        trashImg.src = "https://melab.lnu.se/~hh223ji/uppgift/public/images/trash.png";
        trashImg.classList.add("trashImg");
        trashDiv.appendChild(trashImg);
        div.appendChild(trashDiv);
    }
    div.append(shareBtn);
    div.addEventListener("click", () => redirectPage(list.id));
    return div;
}

// Öppna dialog för att visa länk för att dela listor
async function openShareDialog(listId) {
    try {
        const response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + listId + "/share");
        if (!response.ok) throw new Error("HTTP error! Status:" + response.status);

        const token = await response.json();
        const shareDialog = document.getElementById("shareDialog");
        shareDialog.showModal();
        shareDialog.innerHTML = "";

        const closeBtn = document.createElement("button");
        closeBtn.textContent = "X";
        closeBtn.classList.add("closeDialog");
        closeBtn.addEventListener("click", () => shareDialog.close());

        const pTag = document.createElement("p");
        pTag.textContent = "https://melab.lnu.se/~hh223ji/uppgift/public/share/" + token.token;

        const copyBtn = document.createElement("button");
        copyBtn.textContent = "Copy Link";
        copyBtn.classList.add("copyBtn");
        copyBtn.addEventListener("click", () => {
            navigator.clipboard.writeText(pTag.textContent)
                .then(() => alert("Länk kopierad!"))
                .catch(err => alert("Misslyckades med att kopiera: " + err));
        });

        shareDialog.append(closeBtn, pTag, copyBtn);
    } catch (error) {
        console.error("Error generating share link:", error);
    }
}

// Omdirigera till specifik lista's sida
function redirectPage(id) {
    window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + id;
}

// Ta bort specifik lista
async function deleteList(id) {
    try {
        const response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + id, { method: "POST" });
        if (!response.ok) throw new Error("HTTP error! Status:" + response.status);
        showList();
    } catch (error) {
        console.error("Error deleting list:", error);
    }
}

// Spara användar id i en localstorage
async function saveUserId() {
    try {
        const response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/user", { method: "GET" });
        if (!response.ok) throw new Error("HTTP error! Status:" + response.status);
        const data = await response.json();
        localStorage.setItem("userid", data.id);
        localStorage.setItem("username", data.username);
    } catch (error) {
        console.error("Error fetching user data:", error);
    }
}
