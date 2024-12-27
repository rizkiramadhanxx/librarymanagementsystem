<?php
session_start();

// Include the database connection
include('connection.php');

// Check if the user is logged in
if (!isset($_SESSION['isLogin']) || !$_SESSION['isLogin']) {
  header("Location: login.php");
  exit;
}

// Handle Logout request
if (isset($_POST['logout'])) {
  session_destroy();
  header("Location: login.php");
  exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
  $student_id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
  $stmt->bind_param("i", $student_id);
  $stmt->execute();
  $stmt->close();
  header("Location: student.php");
  exit;
}

// Handle add student request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_student'])) {
  $name = $_POST['name'];
  $nim = $_POST['nim'];
  $contact = $_POST['contact'];

  $stmt = $conn->prepare("INSERT INTO students (name, nim, contact) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $name, $nim, $contact);
  $stmt->execute();
  $stmt->close();
  header("Location: student.php");
  exit;
}

// Handle edit student request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
  $student_id = intval($_POST['student_id']);
  $name = $_POST['name'];
  $nim = $_POST['nim'];
  $contact = $_POST['contact'];

  $stmt = $conn->prepare("UPDATE students SET name = ?, nim = ?, contact = ? WHERE id = ?");
  $stmt->bind_param("sssi", $name, $nim, $contact, $student_id,);

  if ($stmt->execute()) {
    $_SESSION['success'] = "Student data successfully updated.";
  } else {
    $_SESSION['error'] = "Failed to update student data.";
  }

  $stmt->close();
  header("Location: student.php");
  exit;
}



// Fetch all students from the database
$stmt = $conn->prepare("SELECT * FROM students");
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Student Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-image: url('uploads/library.jpg');">
  <div class="container mt-5" style="max-width:1000px; background-color: rgba(236, 236, 236, 0.80); margin-bottom: 30px;padding: 30px;">

    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 d-flex justify-content-between">
      <div>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="borrow.php">Borrow</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="student.php">Student</a>
            </li>
          </ul>
        </div>
      </div>
      <?php
      // logout
      if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: login.php");
        exit;
      }
      ?>
      <form action="" class="d-flex" method="POST">
        <button type="submit" name="logout" class="btn btn-danger">Logout</button>
      </form>
    </nav>
    <!-- Form Add Student -->
    <form action="student.php" method="POST" class="mb-4">
      <div class="mb-3">
        <input type="text" name="name" class="form-control" placeholder="Student Name" required />
      </div>
      <div class="mb-3">
        <input type="number" name="nim" class="form-control" placeholder="NIM" required />
      </div>
      <div class="mb-3">
        <input type="number" name="contact" class="form-control" placeholder="Contact" required />
      </div>
      <button type="submit" class="btn btn-primary">Add Student</button>
    </form>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="editForm" method="POST">
            <input type="hidden" name="edit_student" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="editModalLabel">Edit Student</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="student_id" id="editStudentId">
              <div class="mb-3">
                <label for="editName" class="form-label">Name</label>
                <input type="text" class="form-control" id="editName" name="name" required>
              </div>
              <div class="mb-3">
                <label for="editNim" class="form-label">NIM</label>
                <input type="number" class="form-control" id="editNim" name="nim" required>
              </div>
              <div class="mb-3">
                <label for="editContact" class="form-label">Contact</label>
                <input type="number" class="form-control" id="editContact" name="contact" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Student Table -->
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>NIM</th>
          <th>Contact</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $index => $student): ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($student['name']); ?></td>
            <td><?php echo htmlspecialchars($student['nim']); ?></td>
            <td><?php echo htmlspecialchars($student['contact']); ?></td>
            <td>
              <button
                class="btn btn-warning btn-sm edit-button"
                data-bs-toggle="modal"
                data-bs-target="#editModal"
                data-id="<?php echo $student['id']; ?>"
                data-name="<?php echo $student['name']; ?>"
                data-contact="<?php echo $student['contact']; ?>"
                data-nim="<?php echo $student['nim']; ?>">
                Edit
              </button>
              <a href="student.php?delete=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    // JavaScript to load data into the modal
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', () => {
          const studentId = button.getAttribute('data-id');
          const name = button.getAttribute('data-name');
          const contact = button.getAttribute('data-contact');
          const nim = button.getAttribute('data-nim');

          document.getElementById('editStudentId').value = studentId;
          document.getElementById('editName').value = name;
          document.getElementById('editNim').value = nim;
          document.getElementById('editContact').value = contact;
        });
      });
    });
  </script>
</body>

</html>