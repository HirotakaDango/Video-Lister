<?php
// Connect to SQLite database
$db = new PDO('sqlite:database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create the table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS video (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  link TEXT NOT NULL,
  title TEXT NOT NULL
)");

// Handle form submissions for add, edit, and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['add'])) {
    $link = $_POST['link'];
    $title = $_POST['title'];
    $stmt = $db->prepare("INSERT INTO video (link, title) VALUES (:link, :title)");
    $stmt->execute([':link' => $link, ':title' => $title]);
  } elseif (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $link = $_POST['link'];
    $title = $_POST['title'];
    $stmt = $db->prepare("UPDATE video SET link = :link, title = :title WHERE id = :id");
    $stmt->execute([':link' => $link, ':title' => $title, ':id' => $id]);
  } elseif (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $db->prepare("DELETE FROM video WHERE id = :id");
    $stmt->execute([':id' => $id]);
  }
  // Redirect to avoid form resubmission
  header("Location: {$_SERVER['REQUEST_URI']}");
  exit();
}

// Pagination logic
$limit = 4;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch videos with pagination
$stmt = $db->prepare("SELECT * FROM video ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get the total number of videos for pagination
$totalVideos = $db->query("SELECT COUNT(*) FROM video")->fetchColumn();
$totalPages = ceil($totalVideos / $limit);

// Current URL without query parameters
$currentUrl = strtok($_SERVER["REQUEST_URI"], '?');

// Query parameters for pagination links
$queryParams = $_GET;
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Lister</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  </head>
  <body>
    <div class="container mt-4">
      <h1 class="mb-4 fw-bold text-center">Video Lister</h1>

      <!-- Add video form -->
      <form action="" method="POST" class="mb-4">
        <div class="input-group">
          <input type="text" name="link" class="form-control" placeholder="Enter video link" required>
          <input type="text" name="title" class="form-control" placeholder="Enter video title" required>
          <button type="submit" name="add" class="btn btn-primary">Add Video</button>
        </div>
      </form>

      <!-- Video grid -->
      <div class="row row-cols-1 row-cols-md-2 g-2">
        <?php foreach ($videos as $video): ?>
        <div class="col">
          <div class="card h-100 border-0 bg-body-tertiary rounded-4">
            <div class="ratio ratio-16x9 rounded-top-4">
              <iframe frameborder="0" allowfullscreen="" scrolling="no" allow="autoplay;fullscreen" crossorigin="anonymous" playsinline src="<?= $video['link']; ?>" class="rounded-top-4"></iframe>
            </div>
            <div class="card-body">
              <form action="" method="POST" class="d-flex flex-column gap-2">
                <input type="hidden" name="id" value="<?= $video['id']; ?>">
                <input type="text" name="link" class="form-control" value="<?= $video['link']; ?>">
                <input type="text" name="title" class="form-control" value="<?= $video['title']; ?>">
                <div class="d-flex justify-content-between">
                  <button type="submit" name="edit" class="btn btn-warning btn-sm">Edit</button>
                  <button type="submit" name="delete" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <div class="pagination d-flex gap-1 justify-content-center mt-3">
        <?php if ($page > 1): ?>
          <a class="btn btn-sm btn-primary fw-bold" href="<?php echo $currentUrl . '?' . http_build_query(array_merge($queryParams, ['page' => 1])); ?>">
            <i class="bi text-stroke bi-chevron-double-left"></i>
          </a>
        <?php endif; ?>

        <?php if ($page > 1): ?>
          <a class="btn btn-sm btn-primary fw-bold" href="<?php echo $currentUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page - 1])); ?>">
            <i class="bi text-stroke bi-chevron-left"></i>
          </a>
        <?php endif; ?>

        <?php
          // Calculate the range of page numbers to display
          $startPage = max($page - 2, 1);
          $endPage = min($page + 2, $totalPages);

          // Display page numbers within the range
          for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i === $page) {
              echo '<span class="btn btn-sm btn-primary active fw-bold">' . $i . '</span>';
            } else {
              echo '<a class="btn btn-sm btn-primary fw-bold" href="' . $currentUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $i])) . '">' . $i . '</a>';
            }
          }
        ?>

        <?php if ($page < $totalPages): ?>
          <a class="btn btn-sm btn-primary fw-bold" href="<?php echo $currentUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $page + 1])); ?>">
            <i class="bi text-stroke bi-chevron-right"></i>
          </a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
          <a class="btn btn-sm btn-primary fw-bold" href="<?php echo $currentUrl . '?' . http_build_query(array_merge($queryParams, ['page' => $totalPages])); ?>">
            <i class="bi text-stroke bi-chevron-double-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <div class="mt-5"></div>
  </body>
</html>
