<?php
// Include the database connection
include_once('connection.php');

// Initialize variables for error/success messages
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $item_name = $conn->real_escape_string($_POST['item_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = $conn->real_escape_string($_POST['price']);

    // Validate form data
    if (empty($item_name) || empty($description) || empty($price)) {
        $error = "All fields are required!";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Please enter a valid price!";
    } else {
        // Prepare the SQL query to insert the item
        $query = "INSERT INTO price_list (item_name, description, price) VALUES ('$item_name', '$description', '$price')";

        if ($conn->query($query)) {
            $success = "Item added successfully!";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<?php include 'header.php';?>
<body>

<h2>Add New Item to Price List</h2>

<!-- Display success or error messages -->
<?php if (!empty($success)): ?>
    <p class="success"><?php echo $success; ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <p class="error"><?php echo $error; ?></p>
<?php endif; ?>

<!-- Form to add a new item -->
<form  class="form-inline my-2" action="add_item.php" method="POST">
    <label for="item_name">Item Name:</label>
    <input class="form-control" type="text" id="item_name" name="item_name" required>

    <label for="description">Description:</label>
    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>

    <label for="price">Price (USD):</label>
    <input class="form-control" type="text" id="price" name="price" required>

    <button class="btn btn-primary mt-2" type="submit">Add Item</button>
</form>
<?php include 'footer.php';?>
