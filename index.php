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

// Batalkan task selesai
if (isset($_GET['undo'])) {
    $id = intval($_GET['undo']); 

    $stmt = $koneksi->prepare("UPDATE task SET status = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Task berhasil dibatalkan'); window.location='index.php';</script>";
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

// Edit task
if (isset($_POST['edit_task'])) {
    $id = intval($_POST['task_id']);
    $task = trim($_POST['task']);
    $priority = intval($_POST['priority']);
    $due_date = $_POST['due_date'];

    // Validasi input
    if (!empty($task) && in_array($priority, [1, 2, 3]) && !empty($due_date) && strtotime($due_date) >= strtotime(date('Y-m-d'))) {
        $stmt = $koneksi->prepare("UPDATE task SET task = ?, priority = ?, due_date = ? WHERE id = ?");
        $stmt->bind_param("sisi", $task, $priority, $due_date, $id);
        if ($stmt->execute()) {
            echo "<script>alert('Task berhasil diupdate'); window.location='index.php';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Input tidak valid'); window.location='index.php';</script>";
    }
}

// Search task
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Filter task by priority
$filter_priority = '';
if (isset($_GET['filter_priority'])) {
    $filter_priority = intval($_GET['filter_priority']);
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
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        .sidebar {
            width: 300px;
            float: left;
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 20px;
        }
        .content {
            margin-left: 340px;
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 20px;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            background: #ffffff;
            margin-bottom: 20px;
            padding: 20px;
        }
        .btn-add {
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            color: white;
            border: none;
            border-radius: 30px;
            transition: background 0.3s;
        }
        .btn-add:hover {
            background: linear-gradient(45deg, #ff4b2b, #ff416c);
        }
        .btn-action {
            width: 80px;
            border-radius: 20px;
            transition: background 0.3s;
            margin: 5px 2px;
            padding: 5px 10px;
            font-size: 0.9rem;
        }
        .btn-action:hover {
            background-color: #f1f1f1;
        }
        .btn-undo {
            background: linear-gradient(45deg, #6a11cb, #2575fc);
            color: white;
        }
        .btn-undo:hover {
            background: linear-gradient(45deg, #2575fc, #6a11cb);
        }
        .task-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .task-item {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s;
        }
        .task-item:hover {
            transform: translateY(-5px);
        }
        .task-item h5 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .task-item .badge {
            font-size: 0.9rem;
        }
        .task-item .btn-action {
            width: auto;
            margin-top: 10px;
        }
        .task-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .task-warning {
            color: red;
            font-weight: bold;
        }
        .modal-header {
            background: linear-gradient(45deg, #007bff, #6610f2);
            color: white;
        }
        .modal-title {
            font-weight: bold;
        }
        .btn-close {
            color: white;
        }
        h2, h3, h5 {
            font-weight: 700;
        }
        .form-label {
            font-weight: 600;
        }
        .search-filter-container {
            background: none;
            box-shadow: none;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center text-primary fw-bold">Aplikasi To-Do List</h2>

        <div class="sidebar">
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

            <!-- Form untuk mencari Task -->
            <div class="search-filter-container p-3 mt-3">
                <h5 class="fw-bold text-center text-secondary">Cari Task</h5>
                <form action="" method="get">
                    <input type="text" name="search" class="form-control" placeholder="Cari Task" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                    <button type="submit" class="btn btn-primary w-100 mt-3 p-2 fw-bold">Cari</button>
                </form>
            </div>
            <br>

            <!-- Form untuk filter Task berdasarkan prioritas -->
            <div class="search-filter-container p-3 mt-3">
                <h5 class="fw-bold text-center text-secondary">Filter Task Berdasarkan Prioritas</h5>
                <form action="" method="get">
                    <select name="filter_priority" class="form-control">
                        <option value="">-- Pilih Prioritas --</option>
                        <option value="1" <?php echo $filter_priority == 1 ? 'selected' : ''; ?>>Low</option>
                        <option value="2" <?php echo $filter_priority == 2 ? 'selected' : ''; ?>>Medium</option>
                        <option value="3" <?php echo $filter_priority == 3 ? 'selected' : ''; ?>>High</option>
                    </select>
                    <button type="submit" class="btn btn-primary w-100 mt-3 p-2 fw-bold">Filter</button>
                </form>
            </div>
        </div>

        <div class="content">
            <h3 class="text-center text-dark fw-bold mt-4">Daftar Task</h3>
            <div class="task-list">
                <?php
                // Menampilkan task berdasarkan urutan prioritas, pencarian, dan filter prioritas
                $query = "SELECT * FROM task WHERE task LIKE ? ";
                if ($filter_priority !== '') {
                    $query .= "AND priority = ? ";
                }
                $query .= "ORDER BY status ASC, priority DESC, due_date ASC";
                $stmt = $koneksi->prepare($query);
                $search_param = "%$search%";
                if ($filter_priority !== '') {
                    $stmt->bind_param("si", $search_param, $filter_priority);
                } else {
                    $stmt->bind_param("s", $search_param);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $no = 1;

                $tasks = [];
                $overdueTasks = [];

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $isOverdue = strtotime($row['due_date']) < strtotime(date('Y-m-d')) && $row['status'] == 0;
                        if ($isOverdue) {
                            $overdueTasks[] = $row;
                        } else {
                            $tasks[] = $row;
                        }
                    }
                }

                foreach (array_merge($tasks, $overdueTasks) as $row) {
                    $isOverdue = strtotime($row['due_date']) < strtotime(date('Y-m-d')) && $row['status'] == 0;
                    echo "<div class='task-item'>
                        <h5>{$row['task']}</h5>
                        <p>Prioritas: " . 
                            ($row['priority'] == 1 ? "<span class='badge bg-success'>Low</span>" : 
                            ($row['priority'] == 2 ? "<span class='badge bg-warning'>Medium</span>" : 
                            "<span class='badge bg-danger'>High</span>")) . 
                        "</p>
                        <p>Tanggal: {$row['due_date']}</p>
                        <p>Status: " . 
                            ($row['status'] == 0 ? "<span class='badge bg-danger'>Belum Selesai</span>" : "<span class='badge bg-success'>Selesai</span>") . 
                        "</p>";
                    if ($isOverdue) {
                        echo "<p class='task-warning'>Task ini telah melewati deadline!</p>";
                    }
                    echo "<div class='task-actions'>";
                    if ($row['status'] == 0 && !$isOverdue) {
                        echo "<a href='?complete={$row['id']}' class='btn btn-success btn-sm btn-action'><i class='fas fa-check'></i> Selesai</a> ";
                    } elseif ($row['status'] == 1) {
                        echo "<a href='?undo={$row['id']}' class='btn btn-undo btn-sm btn-action'><i class='fas fa-undo'></i> Batalkan</a> ";
                    }
                    echo "<a href='?delete={$row['id']}' class='btn btn-danger btn-sm btn-action' onclick='return confirm(\"Hapus task ini?\")'><i class='fas fa-trash'></i> Hapus</a>
                        <button class='btn btn-warning btn-sm btn-action' onclick='editTask({$row['id']}, \"{$row['task']}\", {$row['priority']}, \"{$row['due_date']}\")'><i class='fas fa-edit'></i> Edit</button>
                        </div>
                    </div>";
                    $no++;
                }

                if (empty($tasks) && empty($overdueTasks)) {
                    echo "<p class='text-center text-secondary'>Belum ada task</p>";
                }

                $stmt->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk edit Task -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="post">
                        <input type="hidden" name="task_id" id="editTaskId">
                        <label class="form-label">Nama Task</label>
                        <input type="text" name="task" id="editTaskName" class="form-control" required>

                        <label class="form-label mt-2">Prioritas</label>
                        <select name="priority" id="editTaskPriority" class="form-control" required>
                            <option value="1">Low</option>
                            <option value="2">Medium</option>
                            <option value="3">High</option>
                        </select>

                        <label class="form-label mt-2">Tanggal</label>
                        <input type="date" name="due_date" id="editTaskDueDate" class="form-control" required>

                        <button type="submit" name="edit_task" class="btn btn-primary w-100 mt-3 p-2 fw-bold">Update Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editTask(id, task, priority, due_date) {
            document.getElementById('editTaskId').value = id;
            document.getElementById('editTaskName').value = task;
            document.getElementById('editTaskPriority').value = priority;
            document.getElementById('editTaskDueDate').value = due_date;
            var editTaskModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
            editTaskModal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>