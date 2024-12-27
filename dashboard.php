<?php
session_start();

// Include the database connection
include('connection.php');

// Check if the user is logged in
if (!isset($_SESSION['isLogin']) || !$_SESSION['isLogin']) {
  header("Location: login.php");
  exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
  $book_id = intval($_GET['delete']);
  $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
  $stmt->bind_param("i", $book_id);
  $stmt->execute();
  $stmt->close();
  header("Location: dashboard.php");
  exit;
}


// Handle add book request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_book'])) {

  // Ambil data dari form
  $title = $_POST['title'];
  $genre = $_POST['genre'];
  $author = $_POST['author'];
  $category = $_POST['category'];
  $quantity = intval($_POST['quantity']);


  // Validasi dan upload file cover
  if (!empty($_FILES['cover']['name'])) {
    $cover = $_FILES['cover']['name'];
    $target = "uploads/" . basename($cover);

    // Pastikan folder uploads/ bisa ditulisi
    if (!is_dir('uploads')) {
      mkdir('uploads', 0777, true); // Buat folder jika belum ada
    }

    if (move_uploaded_file($_FILES['cover']['tmp_name'], $target)) {
      // Simpan data ke database
      $stmt = $conn->prepare("INSERT INTO books (title, genre, author, category, cover, quantity) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssi", $title, $genre, $author, $category, $cover, $quantity);
      $stmt->execute();
      $stmt->close();
      header("Location: dashboard.php"); // Redirect setelah menambahkan buku
      exit;
    } else {
      echo "<div class='alert alert-danger'>Failed to upload cover image.</div>";
    }
  } else {
    echo "<div class='alert alert-danger'>Cover image is required.</div>";
  }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_book'])) {
  $book_id = intval($_POST['book_id']);
  $title = $_POST['title'];
  $genre = $_POST['genre'];
  $author = $_POST['author'];
  $category = $_POST['category'];
  $cover = $_POST['cover'] ?? $_POST['existing_cover'];
  $quantity = intval($_POST['quantity']);


  // Cek apakah file diunggah
  if (!empty($_FILES['cover']['name'])) {
    // Validasi file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['cover']['type'], $allowed_types)) {
      die("File harus berupa gambar (JPEG, PNG, GIF).");
    }

    if ($_FILES['cover']['size'] > 2 * 1024 * 1024) { // Maksimum 2MB
      die("Ukuran file terlalu besar. Maksimum 2MB.");
    }

    // Buat nama file unik
    $cover = time() . "_" . $_FILES['cover']['name'];
    $target = "uploads/" . basename($cover);

    // Buat direktori jika belum ada
    if (!is_dir("uploads")) {
      mkdir("uploads", 0777, true);
    }

    // Simpan file
    if (!move_uploaded_file($_FILES['cover']['tmp_name'], $target)) {
      die("Gagal mengunggah file.");
    }
  }

  // Query untuk update data
  if ($cover) {
    $stmt = $conn->prepare("UPDATE books SET title = ?, genre = ?, author = ?, category = ?, quantity = ?, cover = ? WHERE id = ?");
    $stmt->bind_param("ssssisi", $title, $genre, $author, $category, $quantity, $cover, $book_id);
  } else {
    $stmt = $conn->prepare("UPDATE books SET title = ?, genre = ?, author = ?, category = ?, quantity = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $title, $genre, $author, $category, $quantity, $book_id);
  }

  // Eksekusi query
  if ($stmt->execute()) {
    $_SESSION['success'] = "Data buku berhasil diperbarui.";
  } else {
    $_SESSION['error'] = "Gagal memperbarui data buku.";
  }

  $stmt->close();
  header("Location: dashboard.php");
  exit;
}

// Handle Logout book request 
if (isset($_POST['logout'])) {
  session_destroy();
  header("Location: login.php");
  exit;
}

// handle keyword filter title and category
if (isset($_GET['keyword'])) {
  $keyword = $_GET['keyword'];
  $searchTerm = "%$keyword%"; // Create the search term first
  $stmt = $conn->prepare("SELECT * FROM books WHERE title LIKE ? OR category LIKE ?");
  $stmt->bind_param("ss", $searchTerm, $searchTerm); // Bind the variables
  $stmt->execute();
  $result = $stmt->get_result();
  $books = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}


// Fetch all books from the database
$stmt = $conn->prepare("SELECT * FROM books");
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Library Management</title>
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
    <h2 class="mb-4 text-center">Library Management System</h2>

    <!-- Form Add Book -->
    <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="mb-4">
      <div class="row g-3">
        <div class="col-12">
          <input type="text" name="title" class="form-control" placeholder="Book Title" required />
        </div>
        <div class="col-12">
          <input type="text" name="genre" class="form-control" placeholder="Genre" required />
        </div>
        <div class="col-12">
          <input type="text" name="author" class="form-control" placeholder="Author" required />
        </div>
        <div class="col-12">
          <input type="text" name="category" class="form-control" placeholder="Category" required />
        </div>
        <div class="col-12">
          <input type="file" name="cover" class="form-control" accept="image/*" required />
        </div>
        <div class="col-12">
          <input type="number" name="quantity" class="form-control" placeholder="Quantity" required />
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-3">Add Book</button>
    </form>



    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_book" value="1">
            <div class="modal-header">
              <h5 class="modal-title" id="editModalLabel">Edit Buku</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="book_id" id="editBookId">
              <div class="mb-3">
                <label for="editTitle" class="form-label">Judul</label>
                <input type="text" class="form-control" id="editTitle" name="title" required>
              </div>
              <div class="mb-3">
                <label for="editGenre" class="form-label">Genre</label>
                <input type="text" class="form-control" id="editGenre" name="genre" required>
              </div>
              <div class="mb-3">
                <label for="editAuthor" class="form-label">Penulis</label>
                <input type="text" class="form-control" id="editAuthor" name="author" required>
              </div>
              <div class="mb-3">
                <label for="editCategory" class="form-label">Kategori</label>
                <input type="text" class="form-control" id="editCategory" name="category" required>
              </div>
              <input type="hidden" name="existing_cover" id="editExistingCover">
              <div class="mb-3">
                <label for="editCover" class="form-label">Cover</label>
                <div id="editCoverPreview">
                  <img style="height: 100px;" id="editCoverPreviewImage" alt="Cover Preview" class="img-fluid">
                </div>
                <input type="file" class="form-control mt-2" id="editCover" name="cover" accept="image/*">
              </div>
              <div class="mb-3">
                <label for="editQuantity" class="form-label">Jumlah</label>
                <input type="text" class="form-control" id="editQuantity" name="quantity" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- filter by category and search dalam 1 keyword -->
    <form method="GET" class="mb-4">
      <div class="row g-3">
        <div class="col-12">
          <input type="text" name="keyword" class="form-control" placeholder="keyword" />
        </div>
      </div>
      <button type="submit" class="btn btn-primary mt-3">Search</button>
    </form>

    <!-- Book Table -->
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Genre</th>
          <th>Author</th>
          <th>Category</th>
          <th>Cover</th>
          <th>Quantity</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($books as $index => $book): ?>
          <tr>
            <td><?php echo $index + 1; ?></td>
            <td><?php echo htmlspecialchars($book['title']); ?></td>
            <td><?php echo htmlspecialchars($book['genre']); ?></td>
            <td><?php echo htmlspecialchars($book['author']); ?></td>
            <td><?php echo htmlspecialchars($book['category']); ?></td>
            <td><img src="uploads/<?php echo htmlspecialchars($book['cover']); ?>" alt="Book Cover" style="width: 50px; height: 50px;"></td>
            <td><?php echo $book['quantity']; ?></td>
            <td>
              <button
                class="btn btn-warning btn-sm edit-button"
                data-bs-toggle="modal"
                data-bs-target="#editModal"
                data-id="<?php echo $book['id']; ?>"
                data-title="<?php echo $book['title']; ?>"
                data-genre="<?php echo $book['genre']; ?>"
                data-author="<?php echo $book['author']; ?>"
                data-category="<?php echo $book['category']; ?>"
                data-cover="<?php echo $book['cover']; ?>"
                data-quantity="<?php echo $book['quantity']; ?>">
                Edit
              </button>
              <a href="dashboard.php?delete=<?php echo $book['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script>
    // JavaScript untuk memuat data ke dalam modal
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.edit-button').forEach(button => {
        button.addEventListener('click', () => {
          const bookId = button.getAttribute('data-id');
          const title = button.getAttribute('data-title');
          const genre = button.getAttribute('data-genre');
          const author = button.getAttribute('data-author');
          const category = button.getAttribute('data-category');
          const cover = button.getAttribute('data-cover');
          const quantity = button.getAttribute('data-quantity');

          document.getElementById('editQuantity').value = quantity;
          document.getElementById('editBookId').value = bookId;
          document.getElementById('editTitle').value = title;
          document.getElementById('editGenre').value = genre;
          document.getElementById('editAuthor').value = author;
          document.getElementById('editCategory').value = category;
          document.getElementById('editCoverPreviewImage').src = "uploads/" + cover;
          document.getElementById('editCover').value = cover;
          document.getElementById('editExistingCover').value = cover;

          // default value cover
          const coverPreview = document.getElementById('editCoverPreviewImage');
          coverPreview.src = "uploads/" + cover;
        });
      });

      document.getElementById('editCover').addEventListener('change', function() {
        const coverPreview = document.getElementById('editCoverPreviewImage');
        coverPreview.src = URL.createObjectURL(this.files[0]);
      });
    });
  </script>
</body>

</html>