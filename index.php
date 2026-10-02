
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBSCS Ethiopia - Welcome</title>
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #e67e22;
            --white: #ffffff;
            --light-bg: #f4f7f6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--light-bg);
            color: var(--primary);
            overflow-x: hidden;
        }
        header {
            background: var(--primary);
            color: white;
            padding: 1rem 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .logo span {
            color: var(--accent);
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: 0.3s;
        }
        .nav-links a:hover {
            color: var(--accent);
        }
        .hero {
            position: relative;
            height: 90vh;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), 
                        url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&q=80&w=1920');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: 4rem;
            margin-bottom: 20px;
            text-shadow: 2px 4px 10px rgba(0,0,0,0.5);
        }

        .hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin-bottom: 40px;
            line-height: 1.6;
            font-weight: 300;
        }
        .cta-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .btn {
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
            border: 2px solid var(--accent);
        }

        .btn-primary:hover {
            background: #d35400;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(230, 126, 34, 0.4);
        }
        .btn-clax {
            background: #f1c40f;
            color: #2c3e50;
            box-shadow: 0 4px #d4ac0d;
            font-weight: bold;
        }
        .btn-clax:active {
            box-shadow: 0 0 #d4ac0d;
            transform: translateY(4px);
        }
        .features {
            padding: 80px 10%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            background: white;
        }

        .feature-card {
            text-align: center;
            padding: 40px 20px;
            border-radius: 15px;
            background: #f9f9f9;
            transition: 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .feature-card i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 20px;
            display: block;
            font-style: normal;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .cta-group { flex-direction: column; width: 100%; max-width: 300px; }
            header { padding: 1rem 5%; }
            .nav-links { display: none; }
        }
     .marquee-container {
        width: 100%;
         overflow:hidden; /* Hides the text when it goes off screen */
         white-space: nowrap; /* Keeps text in one line */
}

.marquee-text {
    display: inline-block;
    padding-left: 100%; /* Starts the text from the right side */
    animation: marquee 15s linear infinite; /* 15s is the speed */
    font-size: 4rem;
    text-shadow: 2px 4px 10px rgba(0,0,0,0.5);
    margin-bottom: 20px;
}

/* The Animation */
@keyframes marquee {
    0%   { transform: translate(0, 0); }
    100% { transform: translate(-100%, 0); }
}


    </style>
</head>
<body>

<!-- Updated Audio Element for bus.mp3 -->
<audio id="hornSound" src="bus.mp3" preload="auto"></audio>

<header>
    <div class="logo">OBSCS ETHIOPIA</div>
    <nav class="nav-links">
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<section class="hero">
   
    <div class="marquee-container">
    <h1 class="marquee-text">Smart Travel Starts Here</h1>
</div>

    <p>The Online Bus Station Controlling System (OBSCS) streamlines your journey from ticket booking to arrival. Safe, fast, and reliable transportation management for all of Ethiopia.</p>
    
    <div class="cta-group">
        <a href="login.php" class="btn btn-primary">Get Started</a>
        <!-- Clax Button triggers the JavaScript -->
        <button onclick="playClax()" class="btn btn-clax">📯 CLAX</button>
    </div>
</section>

<section class="features">
    <div class="feature-card">
        <i>🚌</i>
        <h3>For Passengers</h3>
        <p>Book seats instantly, view live schedules, and manage their travel history from their phone.</p>
    </div>
    <div class="feature-card">
        <i>👨‍✈️</i>
        <h3>For Drivers</h3>
        <p>Access digital manifests, update trip status in real-time, and view assigned routes.</p>
    </div>
    <div class="feature-card">
        <i>🏢</i>
        <h3>For Station Staff</h3>
        <p>Verify tickets, manage terminal arrivals.</p>
    </div>
</section>

<footer style="background: #2c3e50; color: #bdc3c7; text-align: center; padding: 30px;">
    <p>&copy; <?php echo date("Y"); ?> OBSCS - Modernizing Ethiopian Transportation</p>
    <p style="font-size: 0.8rem; margin-top: 10px;">Safe Travels Across the country</p>
</footer>

<script>
    function playClax() {
        var audio = document.getElementById("hornSound");
        // Reset sound to 0 so you can click it multiple times rapidly
        audio.currentTime = 0; 
        audio.play().catch(function(error) {
            console.log("Audio playback failed: " + error);
        });
    }
</script>

</body>
</html>