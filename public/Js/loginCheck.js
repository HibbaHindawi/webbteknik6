async function checkLoginStatus() {
    try {
        let response = await fetch('https://melab.lnu.se/~hh223ji/uppgift/public/checkLogin');

        let data = await response.json();
        if (!data.loggedIn) throw new Error("User not logged in");

    } catch (error) {
        console.error("Redirecting due to login status:", error);
        window.location.replace('https://melab.lnu.se/~hh223ji/uppgift/public/login.php');
    }
}

checkLoginStatus();
