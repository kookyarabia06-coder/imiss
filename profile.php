<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = mysqli_real_escape_string($conn, $_POST['firstname']);
    $lastname = mysqli_real_escape_string($conn, $_POST['lastname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $conn->query("UPDATE users SET 
                  firstname = '$firstname',
                  lastname = '$lastname',
                  email = '$email'
                  WHERE id = $user_id");
    
    $_SESSION['fullname'] = $firstname . ' ' . $lastname;
    $message = '<div class="alert alert-success">Profile updated!</div>';
    $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
}
?>
<?php include 'includes/sidebar.php'; ?>

<div style="padding: 20px; max-width: 600px;">
    <h1 style="margin-bottom: 20px;">My Profile</h1>
    
    <?php echo $message ?? ''; ?>
    
    <div class="table-container">
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="firstname" class="form-control" 
                       value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lastname" class="form-control" 
                       value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
            </div>
            
            <button type="submit" class="btn btn-success">Update Profile</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>