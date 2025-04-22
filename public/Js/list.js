async function init() {
    document.getElementById("usernameTag").textContent = localStorage.getItem("username");
    await checkUserAccess();
    getData();
}
window.addEventListener("DOMContentLoaded", init);

// Kolla om användaren har tillgång till listan
async function checkUserAccess() {
    const url = window.location.href;
    const parts = url.split('/');
    const listId = parts[parts.length - 1];
    const userId = localStorage.getItem("userid");
    if (!userId) {
        window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/index.html";
        return;
    }
    try {
        let response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + listId + "/members");
        if (!response.ok) {
            window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/index.html";
            return;
        }
        let data = await response.json();
        if (!data.isMember) {
            window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/index.html";
        }
    } catch (error) {
        window.location.href = "https://melab.lnu.se/~hh223ji/uppgift/public/index.html";
    }
}

// Rendera alla uppgifter
async function loadTasks() {
    const pathParts = window.location.pathname.split("/");
    const id = pathParts[pathParts.length - 1];
    const currentUser = localStorage.getItem("userid");
    try {
        let response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + id + "/tasks");
        let tasks = await response.json();
        const taskList = document.getElementById("taskList");
        taskList.innerHTML = "";
        if (tasks.length === 0) {
            let p = document.createElement("p");
            p.id = "errorMsg";
            p.textContent = "Det finns inga uppgifter, testa lägga till nya uppgifter";
            taskList.appendChild(p);
        }
        tasks.forEach(task => {
            const parentDiv = document.createElement("div");
            parentDiv.classList.add("task");
            if (task.status == "1") {
                parentDiv.classList.add("completed");
            }

            const taskLeft = document.createElement("div");
            taskLeft.classList = "task-left";

            const input = document.createElement("input");
            input.type = "checkbox";
            input.id = task.id;
            input.name = task.name;
            if (task.status == 1) input.checked = true;

            const textContainer = document.createElement("div");

            const label = document.createElement("label");
            label.textContent = "Namn: " + task.name + " - Poäng: " + task.points;
            label.setAttribute("for", task.id);

            const description = document.createElement("p");
            description.innerHTML = "<b>Beskrivning:</b> " + task.description;

            const creator = document.createElement("p");
            if (task.completeUsername != null) {
                creator.innerHTML = "<b>Skapad av:</b> " + task.creatorUsername + " | <b>Klarad av:</b> " + task.completeUsername;
            }
            else {
                creator.innerHTML = "<b>Skapad av:</b> " + task.creatorUsername;
            }
            textContainer.appendChild(label);
            textContainer.appendChild(description);
            textContainer.appendChild(creator);

            taskLeft.appendChild(input);
            taskLeft.appendChild(textContainer);

            const trashDiv = document.createElement("div");
            const img = document.createElement("img");
            img.src = "https://melab.lnu.se/~hh223ji/uppgift/public/images/trash.png";
            img.classList = "trash";
            trashDiv.appendChild(img);
            trashDiv.addEventListener("click", () => {
                if (input.checked === true && task.completeUser !== currentUser) {
                    alert("Bara användaren som klarade uppgiften kan radera detta.");
                    return;
                }
                deleteTask(task.id)
            });

            parentDiv.appendChild(taskLeft);
            parentDiv.appendChild(trashDiv);
            input.addEventListener('change', function () {
                if (input.checked === false && task.completeUser !== currentUser) {
                    alert("Bara användaren som klarade uppgiften kan avmarkera detta.");
                    input.checked = true;
                    return;
                }
                updateTaskStatus(task.id, input.checked ? 1 : 0);
            });

            parentDiv.addEventListener("click", (e) => {
                if (
                    e.target.tagName === "INPUT" ||
                    e.target.classList.contains("trash")
                ) return;
                input.checked = !input.checked;
                if (input.checked === false && task.completeUser !== currentUser) {
                    alert("Bara användaren som klarade uppgiften kan avmarkera den.");
                    input.checked = true;
                    return;
                }
                updateTaskStatus(task.id, input.checked ? 1 : 0);
            });
            taskList.appendChild(parentDiv);
            getUsers();
        });
    } catch (error) {
        console.error("Error loading tasks:", error);
    }
}

// Skapa ny uppgift
async function submitTask(e) {
    e.preventDefault();
    let taskInput = document.getElementById("taskText");
    let points = document.getElementById("taskPoints");
    let description = document.getElementById("taskDescription");
    const pathParts = window.location.pathname.split("/");
    const listId = pathParts[pathParts.length - 1];
    const charCount = document.getElementById("charCount");
    try {
        let response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + listId + "/tasks", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                name: taskInput.value,
                points: points.value,
                description: description.value,
                listId: listId
            })
        });
        let tasks = await response.json();
        if (tasks.message) {
            taskInput.value = "";
            points.value = "";
            description.value = "";
            charCount.textContent = "0 / 200";
            loadTasks();
        } else {
            console.error("Error creating task:", tasks.error);
        }
    } catch (error) {
        console.error("Error creating task:", error);
    }
}

// Uppdatera status av en specifik uppgift
async function updateTaskStatus(id, status) {
    const pathParts = window.location.pathname.split("/");
    const listId = pathParts[pathParts.length - 1];
    const userid = localStorage.getItem("userid");
    try {
        await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/tasks/" + id + "/status", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                taskStatus: status,
                listId: listId,
                userId: userid
            })
        });
        loadTasks();
    } catch (error) {
        console.error("Error updating task status:", error);
    }
}

// Radera specifik uppgift
async function deleteTask(id) {
    const url = window.location.href;
    const parts = url.split('/');
    const listId = parts[parts.length - 1];
    try {
        await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/tasks/" + id, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                listId: listId
            })
        });
        loadTasks();
    } catch (error) {
        console.error("Error deleting task:", error);
    }
}

// Hämta alla användare och rendera det
async function getUsers() {
    const pathParts = window.location.pathname.split("/");
    const id = pathParts[pathParts.length - 1];
    try {
        let response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + id + "/users");
        let users = await response.json();
        let ul = document.getElementById("users");
        ul.innerHTML = "";
        users.forEach(user => {
            let li = document.createElement("li");
            li.textContent = user.username + " - Poäng: " + user.total_points;
            ul.appendChild(li);
        });
    } catch (error) {
        console.error("Error loading users:", error);
    }
}

// Hämta information om listan
async function getData() {
    const url = window.location.href;
    const parts = url.split('/');
    const id = parts[parts.length - 1];
    try {
        let response = await fetch("https://melab.lnu.se/~hh223ji/uppgift/public/lists/" + id + "/data");
        if (!response.ok) {
            throw new Error("Error fetching list data: " + response.status);
        }
        let data = await response.json();
        if (data.error) {
            console.error("Error from backend:", data.error);
            return;
        }
        updatePageContent(data);
        getUsers();
    } catch (error) {
        console.error("Error getting list info:", error);
    }
}

// Skapa alla html element för sidan
function updatePageContent(data) {
    let body = document.querySelector("body");
    let title = document.createElement("h1");
    title.textContent = data.name;
    body.appendChild(title);
    let backBtn = document.createElement("a");
    backBtn.id = "backBtn";
    backBtn.href = "https://melab.lnu.se/~hh223ji/uppgift/public/index.html";
    backBtn.innerHTML = "&#8678; Huvudsida";
    body.appendChild(backBtn);

    let openDialogBtn = document.createElement("button");
    openDialogBtn.innerHTML = "Ny Uppgift &#43;";
    openDialogBtn.id = "newTask";
    body.appendChild(openDialogBtn);

    let dialog = document.createElement("dialog");
    dialog.id = "taskDialog";
    dialog.className = "task-dialog";

    openDialogBtn.addEventListener("click", () => dialog.showModal());

    let closeBtn = document.createElement("button");
    closeBtn.innerHTML = "X";
    closeBtn.className = "closeDialog";
    closeBtn.addEventListener("click", () => dialog.close());
    dialog.appendChild(closeBtn);

    let taskForm = document.createElement("form");
    taskForm.id = "taskForm";
    taskForm.addEventListener("submit", (e) => {
        e.preventDefault();
        submitTask(e);
        dialog.close();
    });

    let namePointsRow = document.createElement("div");
    namePointsRow.className = "row";

    let nameLabel = document.createElement("label");
    nameLabel.setAttribute("for", "taskText");
    nameLabel.textContent = "Namn:";
    let nameInput = document.createElement("input");
    nameInput.id = "taskText";
    nameInput.type = "text";
    nameInput.maxLength = 20;
    nameInput.required = true;

    let pointsLabel = document.createElement("label");
    pointsLabel.setAttribute("for", "taskPoints");
    pointsLabel.textContent = "Poäng (1-20):";
    let pointsInput = document.createElement("input");
    pointsInput.id = "taskPoints";
    pointsInput.type = "number";
    pointsInput.min = 1;
    pointsInput.max = 20;
    pointsInput.required = true;

    namePointsRow.append(nameLabel, nameInput, pointsLabel, pointsInput);

    let descriptionLabel = document.createElement("label");
    descriptionLabel.setAttribute("for", "taskDescription");
    descriptionLabel.textContent = "Beskrivning:";

    let descWrapper = document.createElement("div");
    descWrapper.className = "desc-wrapper";

    let descriptionTextarea = document.createElement("textarea");
    descriptionTextarea.id = "taskDescription";
    descriptionTextarea.rows = 6;
    descriptionTextarea.maxLength = 200;
    descriptionTextarea.required = true;

    let charCount = document.createElement("span");
    charCount.id = "charCount";
    charCount.textContent = "0 / 200";

    descriptionTextarea.addEventListener("input", () => {
        charCount.textContent = descriptionTextarea.value.length + " / 200";
    });

    descWrapper.append(descriptionTextarea, charCount);

    let submitBtn = document.createElement("input");
    submitBtn.type = "submit";
    submitBtn.value = "Skapa Uppgift";

    taskForm.append(namePointsRow, descriptionLabel, descWrapper, submitBtn);
    dialog.appendChild(taskForm);
    body.appendChild(dialog);
    let taskList = document.createElement("div");
    taskList.id = "taskList";

    let userList = document.createElement("ul");
    userList.id = "users";
    let contentWrapper = document.createElement("div");
    contentWrapper.id = "contentWrapper";
    contentWrapper.append(taskList, userList);
    body.appendChild(contentWrapper);
    loadTasks();
}
