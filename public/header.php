<link rel="stylesheet" href="./bootstrap.min.css">
<style>.container{margin-bottom:50px; padding-bottom: 40px;}</style>
    
<nav class="navbar navbar-expand-lg bg-light mt-2">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="../public/">Home</a>
        </li>
          <?php if($_SESSION['role'] == 'Admin'):?>
        <li class="nav-item">
          <a class="nav-link" href="add_questions.php">Add Question</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="questions.php">Manage Question</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="register.php">Add User</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="users.php">Manage User</a>
        </li>
          <?php endif;?>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link">Welcome, <?= $_SESSION['name'];?> <span class="badge bg-success"><?= $_SESSION['role']; ?></span></a></li>
        <li class="nav-item ">
          <a class="nav-link" href="logout.php">Log Out</a>
        </li>
      </ul>
    </div>
  </div>
</nav>