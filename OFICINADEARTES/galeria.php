<?php
require_once 'db.php';
include 'header.php';

$stmt = $pdo->query('SELECT id, foto_path, titulo, created_at FROM sobre WHERE foto_path IS NOT NULL ORDER BY created_at DESC');
$imagens = $stmt->fetchAll();
?>

<h2>Galeria</h2>

<?php if (count($imagens) === 0): ?>
    <p>Nenhuma imagem cadastrada.</p>
<?php else: ?>
    <?php foreach ($imagens as $img): ?>
        <?php if (!empty($img['foto_path']) && file_exists(__DIR__ . '/' . $img['foto_path'])): ?>
            <figure style="display:inline-block; margin:8px; text-align:center;">
                <img src="<?php echo htmlspecialchars($img['foto_path']); ?>" alt="<?php echo htmlspecialchars($img['titulo']); ?>" style="max-width:150px; display:block;">
                <figcaption><?php echo htmlspecialchars($img['titulo']); ?></figcaption>
            </figure>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>