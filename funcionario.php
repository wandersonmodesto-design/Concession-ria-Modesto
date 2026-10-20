<?php
require_once "conexao.php";

$mensagem = "";

$campos = [
    "idfuncionario" => "",
    "nome" => "",
    "cpf" => "",
    "email" => "",
    "telefone" => "",
    "cargo" => "",
    "salario" => ""
];

function hFuncionario($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function buscarFuncionario($conexao, $id) {
    $stmt = $conexao->prepare(
        "SELECT * FROM funcionarios WHERE idfuncionario = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $registro;
}

$id = (int)($_POST["idfuncionario"] ?? 0);

if (isset($_POST["submit"])) {
    $nome = trim($_POST["nome"] ?? "");
    $cpf = preg_replace('/\D/', '', $_POST["cpf"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = preg_replace('/\D/', '', $_POST["telefone"] ?? "");
    $cargo = trim($_POST["cargo"] ?? "");
    $salarioTexto = $_POST["salario"] ?? "";

    if (
        $nome === "" ||
        strlen($nome) > 50 ||
        strlen($cpf) != 11 ||
        $email === "" ||
        strlen($email) > 30 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $telefone === "" ||
        strlen($telefone) > 12 ||
        $cargo === "" ||
        strlen($cargo) > 50 ||
        $salarioTexto === "" ||
        !is_numeric($salarioTexto) ||
        (float)$salarioTexto < 0
    ) {
        $mensagem = "Confira os dados. Nome e cargo aceitam até 50 caracteres, e-mail até 30, telefone até 12 e CPF deve ter 11 números.";
    } else {
        $salario = (float)$salarioTexto;

        $stmt = $conexao->prepare(
            "INSERT INTO funcionarios
            (nome, cpf, email, telefone, cargo, salario)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssssd",
            $nome,
            $cpf,
            $email,
            $telefone,
            $cargo,
            $salario
        );

        if ($stmt->execute()) {
            $id = $conexao->insert_id;
            $mensagem = "Funcionário cadastrado com sucesso!";

            $registro = buscarFuncionario($conexao, $id);

            if ($registro) {
                $campos = $registro;
            }
        } elseif ($stmt->errno == 1062) {
            $mensagem = "Este CPF já está cadastrado.";
        } else {
            $mensagem = "Erro ao cadastrar funcionário: " . $stmt->error;
        }

        $stmt->close();
    }
}

elseif (isset($_POST["consultar"])) {
    if ($id > 0) {
        $registro = buscarFuncionario($conexao, $id);

        if ($registro) {
            $campos = $registro;
            $mensagem = "Funcionário encontrado!";
        } else {
            $mensagem = "Funcionário não encontrado.";
        }
    } else {
        $mensagem = "Informe o ID do funcionário.";
    }
}

elseif (isset($_POST["alterar"])) {
    $nome = trim($_POST["nome"] ?? "");
    $cpf = preg_replace('/\D/', '', $_POST["cpf"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $telefone = preg_replace('/\D/', '', $_POST["telefone"] ?? "");
    $cargo = trim($_POST["cargo"] ?? "");
    $salarioTexto = $_POST["salario"] ?? "";

    if (
        $id <= 0 ||
        $nome === "" ||
        strlen($nome) > 50 ||
        strlen($cpf) != 11 ||
        $email === "" ||
        strlen($email) > 30 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $telefone === "" ||
        strlen($telefone) > 12 ||
        $cargo === "" ||
        strlen($cargo) > 50 ||
        $salarioTexto === "" ||
        !is_numeric($salarioTexto) ||
        (float)$salarioTexto < 0
    ) {
        $mensagem = "Confira o ID e todos os dados do funcionário.";
    } else {
        $salario = (float)$salarioTexto;

        $stmt = $conexao->prepare(
            "UPDATE funcionarios
             SET nome=?, cpf=?, email=?, telefone=?, cargo=?, salario=?
             WHERE idfuncionario=?"
        );

        $stmt->bind_param(
            "sssssdi",
            $nome,
            $cpf,
            $email,
            $telefone,
            $cargo,
            $salario,
            $id
        );

        if ($stmt->execute()) {
            $mensagem = $stmt->affected_rows > 0
                ? "Funcionário alterado com sucesso!"
                : "Nenhuma alteração realizada. Confira os dados e o ID.";
        } elseif ($stmt->errno == 1062) {
            $mensagem = "Este CPF já está cadastrado para outro funcionário.";
        } else {
            $mensagem = "Erro ao alterar funcionário: " . $stmt->error;
        }

        $stmt->close();

        $registro = buscarFuncionario($conexao, $id);

        if ($registro) {
            $campos = $registro;
        }
    }
}

elseif (isset($_POST["excluir"])) {
    if ($id > 0) {
        $stmt = $conexao->prepare(
            "DELETE FROM funcionarios WHERE idfuncionario = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $mensagem = "Funcionário excluído com sucesso!";
            $campos = array_fill_keys(array_keys($campos), "");
        } else {
            $mensagem = "Funcionário não encontrado.";
        }

        $stmt->close();
    } else {
        $mensagem = "Informe o ID do funcionário para excluir.";
    }
}

elseif (isset($_POST["voltar"]) || isset($_POST["avancar"])) {
    $operador = isset($_POST["avancar"]) ? ">" : "<";
    $ordem = isset($_POST["avancar"]) ? "ASC" : "DESC";

    if ($id > 0) {
        $sql = "SELECT * FROM funcionarios
                WHERE idfuncionario $operador ?
                ORDER BY idfuncionario $ordem LIMIT 1";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        $sql = "SELECT * FROM funcionarios
                ORDER BY idfuncionario $ordem LIMIT 1";

        $stmt = $conexao->prepare($sql);
    }

    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($registro) {
        $campos = $registro;
        $mensagem = "Registro carregado!";
    } else {
        $mensagem = "Não existe outro funcionário nessa direção.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Funcionários</title>
    <link href="cadastros.css" rel="stylesheet">
</head>
<body>

<header>
    <div id="logo">
        <img src="imagens/logocarro.png" alt="Logo">
    </div>

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
<form action="funcionario.php" method="post">

    <h2>Gerenciar Funcionários</h2>

    <p><?= hFuncionario($mensagem) ?></p>

    <div class="campo">
        <label for="idfuncionario">ID do Funcionário</label>
        <input type="number" id="idfuncionario" name="idfuncionario"
               value="<?= hFuncionario($campos['idfuncionario']) ?>">
    </div>

    <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" maxlength="50"
               value="<?= hFuncionario($campos['nome']) ?>" required>
    </div>

    <div class="campo">
        <label for="cpf">CPF</label>
        <input type="text" id="cpf" name="cpf"
               maxlength="14"
               placeholder="000.000.000-00"
               value="<?= hFuncionario($campos['cpf']) ?>" required>
    </div>

    <div class="campo">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" maxlength="30"
               value="<?= hFuncionario($campos['email']) ?>" required>
    </div>

    <div class="campo">
        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone"
               maxlength="12"
               placeholder="21999999999"
               value="<?= hFuncionario($campos['telefone']) ?>" required>
    </div>

    <div class="campo">
        <label for="cargo">Cargo</label>
        <input type="text" id="cargo" name="cargo" maxlength="50"
               value="<?= hFuncionario($campos['cargo']) ?>" required>
    </div>

    <div class="campo">
        <label for="salario">Salário (R$)</label>
        <input type="number" id="salario" name="salario"
               min="0" step="0.01"
               value="<?= hFuncionario($campos['salario']) ?>" required>
    </div>

    <button type="submit" name="submit">✏️ Cadastrar</button>
    <button type="submit" name="consultar">📄 Consultar</button>
    <button type="submit" name="alterar">📝 Alterar</button>

    <button type="submit" name="excluir"
            onclick="return confirm('Deseja excluir este funcionário?')">
        ❌ Excluir
    </button>

    <button type="reset">🧹 Limpar</button>
    <button type="submit" name="voltar">⬅️ Voltar</button>
    <button type="submit" name="avancar">➡️ Avançar</button>

</form>
</main>

<footer>
    <label>&copy;2026 Todos os direitos reservados.</label>
</footer>

</body>
</html>

<?php
$conexao->close();
?>
