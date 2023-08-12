<?php
function generateRandomString($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

$redirected = false;
$not_found = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $long_url = $_POST['long_url'];
    $custom_short_url = trim($_POST['custom_short_url']); // Get custom short URL if provided
    
    // Check for duplicate custom short URL
    $database = file('database.txt');
    foreach ($database as $entry) {
        list($stored_short, $stored_long_url, $stored_creation_date) = explode(" --> ", $entry);
        if (trim($stored_short) === $custom_short_url) {
            $error_message = 'Custom name already present.';
            break;
        }
    }
    
    if (empty($error_message)) {
        if (empty($custom_short_url)) {
            $short_url = generateRandomString();
        } else {
            $short_url = $custom_short_url;
        }
    
        $creation_date = date('Y-m-d H:i:s'); // Store the current date and time as creation date
        $database = fopen('database.txt', 'a+');
        fwrite($database, "$short_url --> $long_url --> $creation_date\n");
        fclose($database);
    
        $redirected = true;
        $redirect_url = $_SERVER['PHP_SELF'] . "?short_url=$short_url";
    }
}

if (isset($_GET['short_url'])) {
    $short_url = $_GET['short_url'];
    $database = file('database.txt');
    foreach ($database as $entry) {
        list($stored_short, $long_url, $stored_creation_date) = explode(" --> ", $entry);
        if (trim($stored_short) === $short_url) {
            header("Location: $long_url");
            exit();
        }
    }
    $not_found = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>URL Shortener</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-4">URL Shortener</h1>

    <?php if (!empty($error_message)) { ?>
        <p class="text-red-500"><?php echo $error_message; ?></p>
    <?php } ?>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="mb-4">
        <label for="long_url" class="block font-medium mb-1">Enter the URL to shorten:</label>
        <input type="url" id="long_url" name="long_url" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300" placeholder="https://example.com">
        <label for="custom_short_url" class="block font-medium mt-2 mb-1">Custom Short URL (optional):</label>
        <input type="text" id="custom_short_url" name="custom_short_url" class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300" placeholder="custom">
        <button type="submit" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Shorten URL</button>
    </form>

    <h2 class="text-2xl font-bold mt-4">Recently Shortened URLs</h2>
    <table class="w-full border-collapse border mt-2">
        <thead>
            <tr>
                <th class="border p-2">Short URL</th>
                <th class="border p-2">Original URL</th>
                <th class="border p-2">IP Address</th>
                <th class="border p-2">Location</th>
                <th class="border p-2">Creation Date</th>
                <th class="border p-2">QR Code</th>
            </tr>
        </thead>
        <tbody>
            <?php
            date_default_timezone_set('Asia/Kolkata'); // Set the timezone to Indian Standard Time (IST)

            $database = file('database.txt');
            $recent_urls = array_slice($database, -5); // Display the last 5 entries
            foreach ($recent_urls as $entry) {
                list($short_url, $long_url, $creation_date) = explode(" --> ", $entry);
                echo "<tr>";
                echo "<td class='border p-2'><a href='?short_url=$short_url' class='underline'>$short_url</a></td>";
                echo "<td class='border p-2'>$long_url</td>";

                // Get user IP address and location using cURL
                $ch = curl_init("https://ipinfo.io/" . $_SERVER['REMOTE_ADDR'] . "/json");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $ip_details = curl_exec($ch);
                curl_close($ch);

                $ip_details = json_decode($ip_details);
                $ip = $ip_details->ip;
                $city = $ip_details->city;
                $region = $ip_details->region;
                $country = $ip_details->country;

                echo "<td class='border p-2'>$ip</td>";
                echo "<td class='border p-2'>$city, $region, $country</td>";

                echo "<td class='border p-2'>$creation_date</td>"; // Display the creation date

                $full_short_url = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/index.php?short_url=" . $short_url;
                $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($full_short_url) . "&size=100x100";
                echo "<td class='border p-2'><img src='$qr_code_url' alt='QR Code'></td>"; // Display QR code image
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
