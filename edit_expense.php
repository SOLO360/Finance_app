<?php
include_once('connection.php');

// Fetch the expense data
$id = $_GET['id'];
$sql = "SELECT * FROM expenses WHERE id = $id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// Update the expense
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $vendor = $_POST['vendor'];
    $staff = $_POST['staff'];

    $sql = "UPDATE expenses SET description='$description', amount='$amount', expense_date='$expense_date', category='$category', quantity='$quantity', vendor='$vendor', staff='$staff' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header('Location: view_expenses.php');
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>
<?php include "header.php"?>
<h2>Edit Expense</h2>
<form method="POST">
    <div class="form-group">
        <label>Description</label>
        <input type="text" name="description" value="<?php echo $row['description']; ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Amount</label>
        <input type="number" name="amount" value="<?php echo $row['amount']; ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="expense_date" value="<?php echo $row['expense_date']; ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Category</label>
        <input type="text" name="category" value="<?php echo $row['category']; ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Quantity</label>
        <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Vendor</label>
        <input type="text" name="vendor" value="<?php echo $row['vendor']; ?>" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Staff</label>
        <input type="text" name="staff" value="<?php echo $row['staff']; ?>" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Update Expense</button>
</form>
<?php include "footer.php"?>