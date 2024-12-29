<?php
session_start();
include('connection.php');

// Check if the user is logged in
if (!isset($_SESSION['isLogin']) || !$_SESSION['isLogin']) {
  header("Location: login.php");
  exit;
}

// Handle edit borrowed book request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_borrowed'])) {
  $borrowed_id = intval($_POST['borrowed_id']);
  $due_date = $_POST['due_date'];
  $status = $_POST['status'];
  echo $status;

  // if return add quantity
  if ($status == 'returned') {
    $book_id = intval($_POST['book_id']);
    $stmt = $conn->prepare("UPDATE books SET quantity = quantity + 1 WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();
  }

  if ($status == 'borrowed') {
    $book_id = intval($_POST['book_id']);
    $stmt = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();
  }

  $stmt = $conn->prepare("UPDATE borrowed SET due_date = ?, status = ? WHERE id = ?");
  $stmt->bind_param("ssi", $due_date, $status, $borrowed_id);

  if ($stmt->execute()) {
    $_SESSION['success'] = "Borrowed book updated successfully.";
  } else {
    $_SESSION['error'] = "Failed to update borrowed book.";
  }

  $stmt->close();
  header("Location: borrow.php");
  exit;
}

// delete borrowed book
if (isset($_GET['delete'])) {
  $borrowed_id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM borrowed WHERE id = ?");
  $stmt->bind_param("i", $borrowed_id);
  $stmt->execute();
  $stmt->close();
  header("Location: borrow.php");
  exit;
}

// Fetch borrowed books
$borrowed_books = $conn->query("
    SELECT 
        borrowed.id,
        students.name AS student_name,
        books.title AS book_name,
        borrowed.due_date,
        borrowed.status 
    FROM borrowed 
    JOIN students ON borrowed.student_id = students.id 
    JOIN books ON borrowed.book_id = books.id
")->fetch_all(MYSQLI_ASSOC);
// Fetch students and books for the dropdown
$students = $conn->query("SELECT id, name  FROM students")->fetch_all(MYSQLI_ASSOC);
$books = $conn->query("SELECT id, title, quantity FROM books WHERE quantity > 0")->fetch_all(MYSQLI_ASSOC);

// handle search 
if (isset($_GET['search'])) {
  $search = $_GET['search'];
  $borrowed_books = $conn->query("
    SELECT 
        borrowed.id,
        students.name AS student_name,
        books.title AS book_name,
        borrowed.due_date,
        borrowed.status 
    FROM borrowed 
    JOIN students ON borrowed.student_id = students.id 
    JOIN books ON borrowed.book_id = books.id 
    WHERE books.title LIKE '%$search%' OR students.name LIKE '%$search%'
  ")->fetch_all(MYSQLI_ASSOC);
}



// Handle borrow request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $student_id = intval($_POST['student_id']);
  $book_id = intval($_POST['book_id']);
  $due_date = $_POST['due_date'];

  // Update books table and insert into borrowed table
  $conn->begin_transaction();
  try {
    $stmt = $conn->prepare("INSERT INTO borrowed (student_id, book_id, due_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $student_id, $book_id, $due_date);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $_SESSION['success'] = "Book borrowed successfully.";
  } catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Failed to borrow book.";
  }

  header("Location: borrow.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Borrow Book</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <h2 class="mb-1">Borrow Book</h2>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success"><?php echo $_SESSION['success'];
                                        unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                      unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Borrow Form -->
    <form action="borrow.php" method="POST">
      <div class="mb-3">
        <label for="student_id" class="form-label">Select Student</label>
        <select name="student_id" id="student_id" class="form-select" required>
          <option value="">-- Select Student --</option>
          <?php foreach ($students as $student): ?>
            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="book_id" class="form-label">Select Book</label>
        <select name="book_id" id="book_id" class="form-select" required>
          <option value="">-- Select Book --</option>
          <?php foreach ($books as $book): ?>
            <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']) . " (Available: " . $book['quantity'] . ")"; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="due_date" class="form-label">Due Date</label>
        <input type="date" id="due_date" name="due_date" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Borrow Book</button>
    </form>
    <!-- /* Search book -->
    <div class="mb-3 mt-4">
      <form action="borrow.php" method="GET">
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Search by Name or Book Title" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
          <button type="submit" class="btn btn-primary">Search</button>
        </div>
      </form>

    </div>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Book</th>
          <th>Due Date</th>
          <th>late</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($borrowed_books) > 0): ?>
          <?php foreach ($borrowed_books as $index => $borrow): ?>
            <tr>
              <td><?php echo $index + 1; ?></td>
              <td><?php echo htmlspecialchars($borrow['student_name']); ?></td>
              <td><?php echo htmlspecialchars($borrow['book_name']); ?></td>
              <td><?php echo htmlspecialchars($borrow['due_date']); ?></td>
              <td>
                <!-- /* jika tanggal sekarang lebih besar dari tanggal jatuh tempo, display ketika status borrowed 3 status on going -->
                <?php if (date('Y-m-d') > $borrow['due_date'] && $borrow['status'] == 'borrowed') : ?>
                  <span class="badge bg-danger">Late
                    <!-- jumlah hari terlambat -->
                    <?php
                    $today = new DateTime(date('Y-m-d'));
                    $dueDate = new DateTime($borrow['due_date']);
                    $interval = $today->diff($dueDate);
                    echo $interval->format('%a') . ' days';
                    ?>
                  </span>
                <?php else : ?>
                  <span class="badge bg-success">no</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                $status = $borrow['status'];
                if ($status == 'borrowed') {
                  echo '<span class="badge bg-warning">' . $status . '</span>';
                } else {
                  echo '<span class="badge bg-success">' . $status . '</span>';
                }
                ?>
              </td>
              <td>
                <a href="borrow.php?delete=<?php echo $borrow['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                <button
                  class="btn btn-warning btn-sm edit-button"
                  data-bs-toggle="modal"
                  data-bs-target="#editModal"
                  data-id="<?php echo $borrow['id']; ?>"
                  data-due-date="<?php echo $borrow['due_date']; ?>"
                  data-status="<?php echo $borrow['status']; ?>">
                  Edit
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center">No borrowed books found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="borrow.php" method="POST">
            <input type="hidden" name="edit_borrowed" value="1">
            <input type="hidden" name="borrowed_id" id="editBorrowedId">
            <div class="modal-header">
              <h5 class="modal-title" id="editModalLabel">Edit Borrowed Book</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="editDueDate" class="form-label">Due Date</label>
                <input type="date" class="form-control" id="editDueDate" name="due_date" required>
              </div>
              <div class="mb-3">
                <label for="editStatus" class="form-label">Status</label>
                <select name="status" id="editStatus" class="form-select" required>
                  <option value="borrowed">Borrowed</option>
                  <option value="returned">Returned</option>
                </select>
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
  </div>

  <script>
    // JavaScript to populate the modal with the current data
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', () => {
          const id = button.getAttribute('data-id');
          const dueDate = button.getAttribute('data-due-date');
          const status = button.getAttribute('data-status');

          document.getElementById('editBorrowedId').value = id;
          document.getElementById('editDueDate').value = dueDate;
          document.getElementById('editStatus').value = status;
        });
      });
    });
  </script>
</body>

</html>