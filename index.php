<?php
// Koneksi ke database
$koneksi = new mysqli("localhost", "root", "", "ukk2025_todolist");

// Cek koneksi
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Tambah task
if (isset($_POST['add_task'])) {
    $task = trim($_POST['task']);
    $priority = intval($_POST['priority']);
    $due_date = $_POST['due_date'];

    // Validasi input
    if (!empty($task) && in_array($priority, [1, 2, 3]) && !empty($due_date) && strtotime($due_date) >= strtotime(date('Y-m-d'))) {
        // Cek duplikasi task
        $stmt = $koneksi->prepare("SELECT COUNT(*) FROM task WHERE task = ?");
        $stmt->bind_param("s", $task);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            $stmt = $koneksi->prepare("INSERT INTO task (task, priority, due_date, status) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sis", $task, $priority, $due_date);
            if ($stmt->execute()) {
                header("Location: index.php");
                exit();
            }
            $stmt->close();
        } else {
            echo "<script>alert('Task sudah ada'); window.location='index.php';</script>";
        }
    } else {
        echo "<script>alert('Input tidak valid'); window.location='index.php';</script>";
    }
}

// Task selesai 
if (isset($_GET['complete'])) {
    $id = intval($_GET['complete']); 

    $stmt = $koneksi->prepare("UPDATE task SET status = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Task berhasil diselesaikan'); window.location='index.php';</script>";
    }
    $stmt->close();
}

// Hapus task 
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $koneksi->prepare("DELETE FROM task WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Task berhasil dihapus'); window.location='index.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Todo List | UKK RPL 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 700px;
        }
        .card {
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-add {
            background: linear-gradient(45deg, #007bff, #6610f2);
            color: white;
            border: none;
        }
        .btn-add:hover {
            background: linear-gradient(45deg, #0056b3, #520dc2);
        }
        .btn-action {
            width: 90px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center text-primary fw-bold">Aplikasi To-Do List</h2>

        <!-- Form untuk menambah Task -->
        <div class="card p-3 mt-3">
            <h5 class="fw-bold text-center text-secondary">Tambah Task Baru</h5>
            <form action="" method="post">
                <label class="form-label">Nama Task</label>
                <input type="text" name="task" class="form-control" placeholder="Masukkan Task Baru" autocomplete="off" required>

                <label class="form-label mt-2">Prioritas</label>
                <select name="priority" class="form-control" required>
                    <option value="">-- Pilih Prioritas --</option>
                    <option value="1">Low</option>
                    <option value="2">Medium</option>
                    <option value="3">High</option>
                </select>

                <label class="form-label mt-2">Tanggal</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>

                <button type="submit" name="add_task" class="btn btn-add w-100 mt-3 p-2 fw-bold">Tambah Task</button>
            </form>
        </div>
        <br>

        <!-- Tabel untuk menampilkan daftar Task -->
        <h3 class="text-center text-dark fw-bold mt-4">Daftar Task</h3>
        <table class="table table-hover shadow-sm bg-white rounded">
            <thead class="bg-primary text-white">
                <tr>
                    <th>No</th>
                    <th>Task</th>
                    <th>Prioritas</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Menampilkan task berdasarkan urutan prioritas
                $query = "SELECT * FROM task ORDER BY status ASC, priority DESC, due_date ASC";
                $result = $koneksi->query($query);
                $no = 1;

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td class='text-center'>$no</td>
                            <td>{$row['task']}</td>
                            <td class='text-center'>" . 
                                ($row['priority'] == 1 ? "<span class='badge bg-success'>Low</span>" : 
                                ($row['priority'] == 2 ? "<span class='badge bg-warning'>Medium</span>" : 
                                "<span class='badge bg-danger'>High</span>")) . 
                            "</td>
                            <td>{$row['due_date']}</td>
                            <td class='text-center'>" . 
                                ($row['status'] == 0 ? "<span class='badge bg-danger'>Belum Selesai</span>" : "<span class='badge bg-success'>Selesai</span>") . 
                            "</td>
                            <td class='text-center'>";
                        if ($row['status'] == 0) {
                            echo "<a href='?complete={$row['id']}' class='btn btn-success btn-sm btn-action'><i class='fas fa-check'></i> Selesai</a> ";
                        }
                        echo "<a href='?delete={$row['id']}' class='btn btn-danger btn-sm btn-action' onclick='return confirm(\"Hapus task ini?\")'><i class='fas fa-trash'></i> Hapus</a>
                            </td>
                        </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center text-secondary'>Belum ada task</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>