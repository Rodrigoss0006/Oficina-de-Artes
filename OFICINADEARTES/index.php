<?php
require_once 'db.php';
include 'header.php';


$stmt = $pdo->query("SELECT * FROM sobre ORDER BY created_at DESC LIMIT 1");
$sobre = $stmt->fetch();
?>

<h2>Home</h2>

<?php if ($sobre): ?>
    <article>
        <h3><?php echo htmlspecialchars($sobre['titulo']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($sobre['descricao'])); ?></p>
        <?php if (!empty($sobre['foto_path']) && file_exists($sobre['foto_path'])): ?>
            <p><img src="<?php echo htmlspecialchars($sobre['foto_path']); ?>" alt="Foto" style="max-width:400px;"></p>
        <?php endif; ?>
        <p><small>Publicado em: <?php echo htmlspecialchars($sobre['created_at']); ?></small></p>
    </article>
<?php else: ?>
    <p>Nenhum conteúdo sobre a oficina cadastrado ainda.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>