<?php
require_once "conexao.php";

$mensagem = "";
$campos = [
    "idcliente" => "",
    "nome" => "",
    "cpf" => "",
    "email" => "",
    "telefone" => "",
    "cidade" => ""
];

function h($v) {
    return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8");
}

function carregarCliente($conexao, $id) {
    $stmt = $conexao->prepare(
        "SELECT * FROM clientes WHERE idcliente = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $registro;
}

$id = (int)($_POST["idcliente"] ?? 0);

if (isset($_POST["submit"])) {
    $nome = trim($_POST["nome"] ?? "");
    $cpf = preg_replace('/\D/', '', $_POST["cpf"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");

    if ($nome === "" || strlen($cpf) != 11) {
        $mensagem = "Informe o nome e o CPF com 11 dígitos.";
    } else {
        $stmt = $conexao->prepare(
            "INSERT INTO clientes (nome, cpf, email, telefone, cidade)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssss", $nome, $cpf, $email, $telefone, $cidade
        );

        if ($stmt->execute()) {
            $id = $conexao->insert_id;
            $mensagem = "Cliente cadastrado com sucesso!";
        } elseif ($stmt->errno == 1062) {
            $mensagem = "Este CPF já está cadastrado.";
        } else {
            $mensagem = "Erro ao cadastrar cliente: " . $stmt->error;
        }
        $stmt->close();
    }
}

elseif (isset($_POST["consultar"])) {
    if ($id > 0) {
        $registro = carregarCliente($conexao, $id);
        if ($registro) {
            $campos = $registro;
            $mensagem = "Cliente encontrado.";
        } else {
            $mensagem = "Cliente não encontrado.";
        }
    } else {
        $mensagem = "Informe o ID do cliente.";
    }
}

elseif (isset($_POST["alterar"])) {
    $nome = trim($_POST["nome"] ?? "");
    $cpf = preg_replace('/\D/', '', $_POST["cpf"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");

    if ($id <= 0 || $nome === "" || strlen($cpf) != 11) {
        $mensagem = "Informe o ID, nome e CPF válido.";
    } else {
        $stmt = $conexao->prepare(
            "UPDATE clientes
             SET nome=?, cpf=?, email=?, telefone=?, cidade=?
             WHERE idcliente=?"
        );
        $stmt->bind_param(
            "sssssi", $nome, $cpf, $email, $telefone, $cidade, $id
        );

        if ($stmt->execute()) {
            $mensagem = $stmt->affected_rows > 0
                ? "Cliente alterado com sucesso."
                : "Nenhuma alteração realizada; confira se o ID existe.";
        } else {
            $mensagem = $stmt->errno == 1062
                ? "Este CPF já pertence a outro cliente."
                : "Erro ao alterar: " . $stmt->error;
        }
        $stmt->close();
    }
}

elseif (isset($_POST["excluir"])) {
    if ($id > 0) {
        $stmt = $conexao->prepare(
            "DELETE FROM clientes WHERE idcliente=?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $mensagem = $stmt->affected_rows > 0
            ? "Cliente excluído com sucesso."
            : "Cliente não encontrado.";
        $stmt->close();

        if ($mensagem == "Cliente excluído com sucesso.") {
            $campos = array_fill_keys(array_keys($campos), "");
        }
    } else {
        $mensagem = "Informe o ID do cliente para excluir.";
    }
}

elseif (isset($_POST["voltar"]) || isset($_POST["avancar"])) {
    $operador = isset($_POST["avancar"]) ? ">" : "<";
    $ordem = isset($_POST["avancar"]) ? "ASC" : "DESC";

    if ($id > 0) {
        $sql = "SELECT * FROM clientes
                WHERE idcliente $operador ?
                ORDER BY idcliente $ordem LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        $sql = "SELECT * FROM clientes ORDER BY idcliente $ordem LIMIT 1";
        $stmt = $conexao->prepare($sql);
    }

    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($registro) {
        $campos = $registro;
        $mensagem = "Registro carregado.";
    } else {
        $mensagem = "Não há mais registros nessa direção.";
    }
}

if (isset($_POST["submit"]) && $id > 0) {
    $registro = carregarCliente($conexao, $id);
    if ($registro) $campos = $registro;
}

if (isset($_POST["alterar"]) && $id > 0) {
    $registro = carregarCliente($conexao, $id);
    if ($registro) $campos = $registro;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerenciar Clientes</title>
    <link href="cadastros.css" rel="stylesheet">
</head>
<body>
<header>
    <div id="logo"><img src="imagens/logocarro.png" alt="Logo"></div>
    <div id="texto">
        <h1>Concessionária <span>Modesto</span></h1>
        <label>A mais modesta da região.</label>
    </div>
    <div id="inicio">
        <a href="index.html">
            <img src="imagens/botao-home.png" alt="Início">
            <label>Início</label>
        </a>
    </div>
</header>
<main>
<form action="cliente.php" method="post">
    <h2>Gerenciar Clientes</h2>
    <p><?= h($mensagem) ?></p>

    <div class="campo">
        <label for="idcliente">ID do Cliente</label>
        <input type="number" id="idcliente" name="idcliente"
               value="<?= h($campos['idcliente']) ?>">
    </div>
    <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome"
               value="<?= h($campos['nome']) ?>">
    </div>
    <div class="campo">
        <label for="cpf">CPF</label>
        <input type="text" id="cpf" name="cpf"
               value="<?= h($campos['cpf']) ?>">
    </div>
    <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email"
               value="<?= h($campos['email']) ?>">
    </div>
    <div class="campo">
        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone"
               value="<?= h($campos['telefone']) ?>">
    </div>
    <div class="campo">
        <label for="cidade">Cidade</label>
        <input type="text" id="cidade" name="cidade"
               value="<?= h($campos['cidade']) ?>">
    </div>

    <button type="submit" name="submit">✏️ Cadastrar</button>
    <button type="submit" name="consultar">📄 Consultar</button>
    <button type="submit" name="alterar">📝 Alterar</button>
    <button type="submit" name="excluir"
            onclick="return confirm('Deseja excluir este cliente?')">
        ❌ Excluir
    </button>
    <button type="reset">🧹 Limpar</button>
    <button type="submit" name="voltar">⬅️ Voltar</button>
    <button type="submit" name="avancar">➡️ Avançar</button>
</form>
</main>
<footer><label>&copy;2026 Todos os direitos reservados.</label></footer>
</body>
</html>
<?php $conexao->close(); ?>
