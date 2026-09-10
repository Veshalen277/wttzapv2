<?php
include '../header.php';

if (isset($_SESSION['u_data'])) {
    $user = $_SESSION['u_data'];
}

// Fetch user details from the database based on $user[5] (user ID)
$id = $user[5];
$sql = "SELECT * FROM users_tbl WHERE id = $id";
$query = mysqli_query($con, $sql);
$result = mysqli_fetch_assoc($query);

$user_id = $id;

/* --------------------------------------------------------------------------
| Handle adding a new checklist item                                         |
|----------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item'])) {
    $item = $_POST['item'];

    // $stmt = $con->prepare("INSERT INTO checklist_items (user_id, item) VALUES (?, ?)");
    // $stmt->bind_param("is", $user_id, $item);
    // Replaced with the bottom
    $stmt = $con->prepare("INSERT INTO checklist_items (user_id, item, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $item);

    $stmt->execute();
    $stmt->close();
}

/* --------------------------------------------------------------------------
| Handle toggling item checked status                                        |
|----------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['delete_id'])) {
    $toggle_id = $_POST['id'];
    $stmt      = $con->prepare("UPDATE checklist_items SET checked = NOT checked WHERE id = ?");
    $stmt->bind_param("i", $toggle_id);
    $stmt->execute();
    $stmt->close();
}

/* --------------------------------------------------------------------------
| Handle deleting a single item                                              |
|----------------------------------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt      = $con->prepare("DELETE FROM checklist_items WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
}

/* --------------------------------------------------------------------------
| Handle bulk delete of checked items                                        |
|----------------------------------------------------------------------------*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_checked']) &&
    !empty($_POST['checked_ids']) &&
    is_array($_POST['checked_ids'])
) {
    $ids_to_delete = array_map('intval', $_POST['checked_ids']);
    $placeholders  = implode(',', array_fill(0, count($ids_to_delete), '?'));
    $types         = str_repeat('i', count($ids_to_delete));

    $stmt = $con->prepare("DELETE FROM checklist_items WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$ids_to_delete);
    $stmt->execute();
    $stmt->close();
}

/* --------------------------------------------------------------------------
| Fetch the user's checklist items                                           |
|----------------------------------------------------------------------------*/
$result = $con->query("SELECT * FROM checklist_items WHERE user_id = $user_id ORDER BY created_at DESC");
?>

<style>
    .checklist_container {
        max-width: 900px;
        margin-top: 50px;
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        background-color: #f9f9f9;
    }

    .checklist_container h2 {
        font-size: 1.5rem;
        text-align: center;
        margin-bottom: 20px;
    }

    .list-group-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        border: 1px solid #ddd;
        margin-bottom: 8px;
        background-color: #fff;
        border-radius: 5px;
    }

    .checked {
        text-decoration: line-through;
        color: gray;
    }

    .btn-container {
        display: flex;
        /* justify-content: space-between; */
        margin-top: 10px;
    }

    .form-container input[type="text"] {
        width: 80%;
        padding: 8px;
        margin-right: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .form-container button {
        padding: 8px 15px;
        background-color: #007bff;
        border: none;
        color: white;
        border-radius: 4px;
        cursor: pointer;
    }

    .form-container button:hover {
        background-color: #0056b3;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    .copy-btn {
        padding: 8px 15px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .copy-btn:hover {
        background-color: #218838;
    }
</style>

<div class="container">
    <div class="row">
        <div class="col-md-2">
            <?php include '../inc/sidebar.php'; ?>
        </div>
        <div class="col-md-10">
            <h2 class="text-center">My Checklist</h2>

            <div class="container-fluid checklist_container mb-5">
                <!-- Checklist Form -->
                <div class="form-container mb-4">
                    <form method="post">
                        <input type="text" name="item" placeholder="Add a new item" required>
                        <button type="submit">Add</button>
                    </form>
                </div>

                <!-- Copy Button -->
                <button id="copyButton" class="copy-btn mb-3" onclick="copyChecklist()">Copy Checklist</button>

                <!-- Checklist Items - individual forms for toggle/delete -->
                <ul class="list-group">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li class="list-group-item">
                            <form method="post" action="">
                                <input type="checkbox" name="id" value="<?php echo $row['id']; ?>" <?php echo $row['checked'] ? 'checked' : ''; ?> onchange="this.form.submit();">

                                <div>
                                    <span class="<?php echo $row['checked'] ? 'checked' : ''; ?>">
                                        <?php echo htmlspecialchars($row['item']); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        Added on: <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                    </small>
                                </div>

                                <div class="btn-container">
                                    <button type="button" class="btn btn-success btn-sm" onclick="editItem(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['item']); ?>')"><i class="bi bi-pencil-fill"></i> Edit</button>
                                    <button type="submit" name="delete_id" value="<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i> Delete</button>
                                </div>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <!-- Bulk Delete Form -->
                <form method="post" id="bulkDeleteForm">
                    <input type="hidden" name="delete_checked" value="1">
                    <button type="submit" class="btn btn-danger mt-3">Delete All Checked</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Checklist Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_id" id="edit-id">
                    <div class="mb-3">
                        <label for="edit-item" class="form-label">Item</label>
                        <input type="text" class="form-control" name="edit_item" id="edit-item" required>
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

<?php
/* --------------------------------------------------------------------------
| PHP script to handle the edit of checklist item                            |
|----------------------------------------------------------------------------*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['edit_id']) &&
    isset($_POST['edit_item'])
) {
    $edit_id   = $_POST['edit_id'];
    $edit_item = $_POST['edit_item'];

    $stmt = $con->prepare("UPDATE checklist_items SET item = ? WHERE id = ?");
    $stmt->bind_param("si", $edit_item, $edit_id);
    $stmt->execute();
    $stmt->close();
}
?>

<script>
// Create an EventSource to connect to the SSE
const eventSource = new EventSource('sse.php');

// Listen for messages
eventSource.onmessage = function(event) {
    const data = JSON.parse(event.data);

    // Clear the checklist and repopulate it with updated data
    const checklist = document.querySelector('.list-group');
    checklist.innerHTML = ''; // Clear existing items

    data.forEach(item => {
        const listItem = document.createElement('li');
        listItem.className = 'list-group-item';

        const itemHtml = `
            <form method="post" action="">
                <input type="checkbox" name="id" value="${item.id}" ${item.checked ? 'checked' : ''} onchange="this.form.submit();">
                <span style="${item.checked ? 'text-decoration: line-through;' : ''}">
                    ${item.item}
                </span>
                <button type="submit" name="delete_id" value="${item.id}" class="m-1 btn btn-danger btn-sm float-end">Delete</button>
            </form>
        `;

        listItem.innerHTML = itemHtml;
        checklist.appendChild(listItem);
    });
};

// Handle SSE errors
eventSource.onerror = function() {
    console.error("Error occurred with SSE connection.");
};

/* Function to copy checklist */
function copyChecklist() {
    let checklistItems = [];
    const items = document.querySelectorAll('.list-group-item');

    items.forEach(item => {
        const text = item.querySelector('span')?.textContent.trim() || '';
        const timestamp = item.querySelector('small')?.textContent.trim() || '';
        checklistItems.push(`${text} (${timestamp})`);
    });

    const textToCopy = checklistItems.join('\n');

    navigator.clipboard.writeText(textToCopy).then(() => {
        alert('Checklist copied to clipboard!');
        window.location.href = '/dashboard/employee/emp_profile.php'; // Optional redirect
    }).catch(err => {
        console.error('Failed to copy: ', err);
    });
}

// Function to edit checklist item
function editItem(id, text) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-item').value = text;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// Bulk delete: inject checked IDs into hidden inputs before submit
const bulkForm = document.getElementById('bulkDeleteForm');
if (bulkForm) {
    bulkForm.addEventListener('submit', function (e) {
        // Clear any existing dynamically added inputs
        this.querySelectorAll('input[name="checked_ids[]"]').forEach(el => el.remove());

        // Collect all checked boxes
        const checkedBoxes = document.querySelectorAll('.list-group-item input[type="checkbox"]:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please check at least one item to delete.');
            return;
        }

        checkedBoxes.forEach(cb => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type   = 'hidden';
            hiddenInput.name   = 'checked_ids[]';
            hiddenInput.value  = cb.value;
            this.appendChild(hiddenInput);
        });
    });
}
</script>

<?php
$con->close();
include '../footer.php';
?>
