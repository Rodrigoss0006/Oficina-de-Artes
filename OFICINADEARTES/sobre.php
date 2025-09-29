<?php
require_once 'db.php';
include 'header.php';


$uploadDir = __DIR__ . '/uploads/';
$uploadWebPathPrefix = 'uploads/';
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$errors = [];
$messages = [];


function slugFileName($filename)
{
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[^A-Za-z0-9\-]/', '_', $name);
    return time() . '_' . $name . '.' . $ext;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($titulo === '') $errors[] = 'Título é obrigatório.';
    if ($descricao === '') $errors[] = 'Descrição é obrigatória.';

    $fotoPath = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erro no upload da foto.';
        } else {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = 'Formato de imagem não permitido.';
            } else {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = slugFileName($_FILES['foto']['name']);
                $dest = $uploadDir . $newName;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                    $errors[] = 'Falha ao mover arquivo.';
                } else {
                    $fotoPath = $uploadWebPathPrefix . $newName;
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO sobre (titulo, descricao, foto_path) VALUES (:titulo, :descricao, :foto_path)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':descricao' => $descricao,
            ':foto_path' => $fotoPath
        ]);
        $messages[] = 'Registro criado com sucesso.';
    }
}


$editItem = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM sobre WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $editItem = $stmt->fetch();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    if ($titulo === '') $errors[] = 'Título é obrigatório.';
    if ($descricao === '') $errors[] = 'Descrição é obrigatória.';


    $stmt = $pdo->prepare('SELECT * FROM sobre WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $current = $stmt->fetch();

    $fotoPath = $current ? $current['foto_path'] : null;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erro no upload da foto.';
        } else {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = 'Formato de imagem não permitido.';
            } else {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = slugFileName($_FILES['foto']['name']);
                $dest = $uploadDir . $newName;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                    $errors[] = 'Falha ao mover arquivo.';
                } else {

                    if ($fotoPath && file_exists(__DIR__ . '/' . $fotoPath)) {
                        @unlink(__DIR__ . '/' . $fotoPath);
                    }
                    $fotoPath = $uploadWebPathPrefix . $newName;
                }
            }
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE sobre SET titulo = :titulo, descricao = :descricao, foto_path = :foto_path WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':descricao' => $descricao,
            ':foto_path' => $fotoPath,
            ':id' => $id
        ]);
        $messages[] = 'Registro atualizado com sucesso.';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM sobre WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();
    if ($item) {

        if (!empty($item['foto_path']) && file_exists(__DIR__ . '/' . $item['foto_path'])) {
            @unlink(__DIR__ . '/' . $item['foto_path']);
        }
        $stmt = $pdo->prepare('DELETE FROM sobre WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $messages[] = 'Registro excluído com sucesso.';
    } else {
        $errors[] = 'Registro não encontrado.';
    }
}


$stmt = $pdo->query('SELECT * FROM sobre ORDER BY created_at DESC');
$all = $stmt->fetchAll();

?>

<h2>Sobre a Oficina (CRUD)</h2>

<?php foreach ($errors as $e): ?>
    <p style="color:red"><?php echo htmlspecialchars($e); ?></p>
<?php endforeach; ?>
<?php foreach ($messages as $m): ?>
    <p style="color:green"><?php echo htmlspecialchars($m); ?></p>
<?php endforeach; ?>


<?php if ($editItem): ?>
    <h3>Editar registro</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo (int)$editItem['id']; ?>">
        <label>Título:<br>
            <input type="text" name="titulo" value="<?php echo htmlspecialchars($editItem['titulo']); ?>" maxlength="150">
        </label><br>
        <label>Descrição:<br>
            <textarea name="descricao" rows="6"><?php echo htmlspecialchars($editItem['descricao']); ?></textarea>
        </label><br>
        <label>Foto (trocar):<br>
            <input type="file" name="foto" accept="image/*">
        </label><br>
        <?php if (!empty($editItem['foto_path']) && file_exists(__DIR__ . '/' . $editItem['foto_path'])): ?>
            <p>Foto atual:<br><img src="<?php echo htmlspecialchars($editItem['foto_path']); ?>" alt="" style="max-width:200px;"></p>
        <?php endif; ?>
        <button type="submit">Salvar alterações</button>
        <a href="sobre.php">Cancelar</a>
    </form>
<?php else: ?>
    <h3>Criar novo registro</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <label>Título:<br>
            <input type="text" name="titulo" maxlength="150">
        </label><br>
        <label>Descrição:<br>
            <textarea name="descricao" rows="6"></textarea>
        </label><br>
        <label>Foto:<br>
            <input type="file" name="foto" accept="image/*">
        </label><br>
        <button type="submit">Criar</button>
    </form>
<?php endif; ?>

<hr>

<h3>Registros existentes</h3>
<?php if (count($all) === 0): ?>
    <p>Nenhum registro ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Foto</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($all as $row): ?>
                <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                    <td>
                        <?php if (!empty($row['foto_path']) && file_exists(__DIR__ . '/' . $row['foto_path'])): ?>
                            <img src="<?php echo htmlspecialchars($row['foto_path']); ?>" alt="" style="max-width:100px;">
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <a href="sobre.php?edit=<?php echo (int)$row['id']; ?>">Editar</a>
                        |
                        <form method="post" style="display:inline" onsubmit="return confirm('Confirma exclusão?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'footer.php'; ?>