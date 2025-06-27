<?php
include_once('connection.php'); // Include your database connection file

// Check if the ID is set in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Fetch the item details
    $query = "SELECT * FROM price_list WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if (!$item) {
        die("Item not found.");
    }
}

// Update the item if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    // Update the item in the database
    $update_query = "UPDATE price_list SET item_name = ?, description = ?, price = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ssdi", $item_name, $description, $price, $id);
    
    if ($update_stmt->execute()) {
        header("Location: price_list.php"); // Redirect to the price list after successful update
        exit();
    } else {
        echo "Error updating item: " . $conn->error;
    }
}
?>

<?php include 'header.php'; ?>
<body>

<h1>Edit Price Item</h1>

<form method="POST" action="">
    <div class="form-group">
        <label for="item_name">Item Name:</label>
        <input class="form-control" type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['item_name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="description">Description:</label>
        <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>
    </div>
    <div class="form-group">
        <label for="price">Price:</label>
        <input class="form-control" type="number" id="price" name="price" value="<?php echo htmlspecialchars($item['price']); ?>" required>
    </div>
    <button class="btn btn-primary my-2" type="submit">Update</button>
</form>

<?php include 'footer.php'; ?>
