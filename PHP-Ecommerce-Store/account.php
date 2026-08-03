<?php
$pageTitle = 'Account | My Store';
include 'includes/header.php';

$errors = [];
$old = [];

// ---------- STATE 2: user already logged in -> fill/update profile ----------
if (!empty($_SESSION['logged_in'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_profile'])) {
        $old = $_POST;

        $username  = trim($_POST['username'] ?? '');
        $password  = trim($_POST['password'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $facebook  = trim($_POST['facebook'] ?? '');
        $twitter   = trim($_POST['twitter'] ?? '');
        $instagram = trim($_POST['instagram'] ?? '');

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Username must be at least 3 characters.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required.';
        } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            $errors['phone'] = 'Phone number must be 10 to 15 digits.';
        }

        if ($facebook === '') {
            $errors['facebook'] = 'Facebook account URL is required.';
        } elseif (!filter_var($facebook, FILTER_VALIDATE_URL)) {
            $errors['facebook'] = 'Please enter a valid Facebook URL.';
        }

        if ($twitter === '') {
            $errors['twitter'] = 'Twitter account URL is required.';
        } elseif (!filter_var($twitter, FILTER_VALIDATE_URL)) {
            $errors['twitter'] = 'Please enter a valid Twitter URL.';
        }

        if ($instagram === '') {
            $errors['instagram'] = 'Instagram account URL is required.';
        } elseif (!filter_var($instagram, FILTER_VALIDATE_URL)) {
            $errors['instagram'] = 'Please enter a valid Instagram URL.';
        }

        if (empty($errors)) {
            $_SESSION['profile'] = [
                'username'  => $username,
                'password'  => $password,
                'email'     => $email,
                'phone'     => $phone,
                'facebook'  => $facebook,
                'twitter'   => $twitter,
                'instagram' => $instagram,
            ];
            header('Location: index.php');
            exit;
        }
    }
    ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="mb-4">Complete Your Profile</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="account.php" novalidate>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                               id="username" name="username" value="<?php echo htmlspecialchars($old['username'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                               id="password" name="password">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                               id="phone" name="phone" value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="facebook">Facebook URL</label>
                        <input type="text" class="form-control <?php echo isset($errors['facebook']) ? 'is-invalid' : ''; ?>"
                               id="facebook" name="facebook" value="<?php echo htmlspecialchars($old['facebook'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="twitter">Twitter URL</label>
                        <input type="text" class="form-control <?php echo isset($errors['twitter']) ? 'is-invalid' : ''; ?>"
                               id="twitter" name="twitter" value="<?php echo htmlspecialchars($old['twitter'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="instagram">Instagram URL</label>
                        <input type="text" class="form-control <?php echo isset($errors['instagram']) ? 'is-invalid' : ''; ?>"
                               id="instagram" name="instagram" value="<?php echo htmlspecialchars($old['instagram'] ?? ''); ?>">
                    </div>
                    <button type="submit" name="submit_profile" class="btn btn-primary btn-block">Save Profile</button>
                </form>
            </div>
        </div>
    </div>

<?php
// ---------- STATE 1: user not logged in -> login form ----------
} else {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_login'])) {
        $old = $_POST;

        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }

        if (empty($errors)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['email'] = $email;
            header('Location: all-products.php');
            exit;
        }
    }
    ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <h2 class="mb-4">Login</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="account.php" novalidate>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="text" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                               id="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                               id="password" name="password">
                    </div>
                    <button type="submit" name="submit_login" class="btn btn-primary btn-block">Login</button>
                </form>
            </div>
        </div>
    </div>

<?php
}

include 'includes/footer.php';
