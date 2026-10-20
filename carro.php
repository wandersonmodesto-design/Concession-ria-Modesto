
<?php
require_once "conexao.php";

$mensagem = "";

$campos = [
    "idcarro" => "",
    "marca" => "",
    "modelo" => "",
    "anofabricacao" => "",
    "cor" => "",
    "placa" => "",
    "km" => "",
    "preco" => "",
    "situacao" => ""
];

function hCarro($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function buscarCarro($conexao, $id) {
    $stmt = $conexao->prepare(
        "SELECT * FROM carros WHERE idcarro = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $registro;
}

$id = (int)($_POST["idcarro"] ?? 0);

if (isset($_POST["submit"])) {
    $marca = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $anoTexto = $_POST["anofabricacao"] ?? "";
    $cor = trim($_POST["cor"] ?? "");
    $placa = strtoupper(trim($_POST["placa"] ?? ""));
    $kmTexto = $_POST["km"] ?? "0";
    $precoTexto = $_POST["preco"] ?? "";
    $situacao = trim($_POST["situacao"] ?? "");

    $ano = (int)$anoTexto;
    $km = filter_var($kmTexto, FILTER_VALIDATE_INT);
    $preco = is_numeric($precoTexto) ? (float)$precoTexto : -1;

    if (
        $marca === "" ||
        strlen($marca) > 30 ||
        $modelo === "" ||
        strlen($modelo) > 30 ||
        $anoTexto === "" ||
        $ano < 1901 ||
        $ano > (int)date("Y") + 1 ||
        $cor === "" ||
        strlen($cor) > 15 ||
        $placa === "" ||
        strlen($placa) != 7 ||
        $km === false ||
        $km < 0 ||
        $precoTexto === "" ||
        !is_numeric($precoTexto) ||
        $preco < 0 ||
        $situacao === "" ||
        strlen($situacao) > 10
    ) {
        $mensagem = "Confira os campos. Marca e modelo aceitam até 30 caracteres, cor até 15, placa 7 e situação até 10.";
    } else {
        $stmt = $conexao->prepare(
            "INSERT INTO carros
            (marca, modelo, anofabricacao, cor, placa, km, preco, situacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssissids",
            $marca,
            $modelo,
            $ano,
            $cor,
            $placa,
            $km,
            $preco,
            $situacao
        );

        if ($stmt->execute()) {
            $id = $conexao->insert_id;
            $mensagem = "Carro cadastrado com sucesso!";

            $registro = buscarCarro($conexao, $id);
            if ($registro) {
                $campos = $registro;
            }
        } elseif ($stmt->errno == 1062) {
            $mensagem = "Esta placa já está cadastrada.";
        } else {
            $mensagem = "Erro ao cadastrar carro: " . $stmt->error;
        }

        $stmt->close();
    }
}

elseif (isset($_POST["consultar"])) {
    if ($id > 0) {
        $registro = buscarCarro($conexao, $id);

        if ($registro) {
            $campos = $registro;
            $mensagem = "Carro encontrado!";
        } else {
            $mensagem = "Carro não encontrado.";
        }
    } else {
        $mensagem = "Informe o ID do carro.";
    }
}

elseif (isset($_POST["alterar"])) {
    $marca = trim($_POST["marca"] ?? "");
    $modelo = trim($_POST["modelo"] ?? "");
    $anoTexto = $_POST["anofabricacao"] ?? "";
    $cor = trim($_POST["cor"] ?? "");
    $placa = strtoupper(trim($_POST["placa"] ?? ""));
    $kmTexto = $_POST["km"] ?? "";
    $precoTexto = $_POST["preco"] ?? "";
    $situacao = trim($_POST["situacao"] ?? "");

    $ano = (int)$anoTexto;
    $km = filter_var($kmTexto, FILTER_VALIDATE_INT);
    $preco = is_numeric($precoTexto) ? (float)$precoTexto : -1;

    if (
        $id <= 0 ||
        $marca === "" ||
        strlen($marca) > 30 ||
        $modelo === "" ||
        strlen($modelo) > 30 ||
        $anoTexto === "" ||
        $ano < 1901 ||
        $ano > (int)date("Y") + 1 ||
        $cor === "" ||
        strlen($cor) > 15 ||
        $placa === "" ||
        strlen($placa) != 7 ||
        $km === false ||
        $km < 0 ||
        $precoTexto === "" ||
        !is_numeric($precoTexto) ||
        $preco < 0 ||
        $situacao === "" ||
        strlen($situacao) > 10
    ) {
        $mensagem = "Confira o ID e todos os dados do carro.";
    } else {
        $stmt = $conexao->prepare(
            "UPDATE carros
             SET marca=?, modelo=?, anofabricacao=?, cor=?,
                 placa=?, km=?, preco=?, situacao=?
             WHERE idcarro=?"
        );

        $stmt->bind_param(
            "ssissidsi",
            $marca,
            $modelo,
            $ano,
            $cor,
            $placa,
            $km,
            $preco,
            $situacao,
            $id
        );

        if ($stmt->execute()) {
            $mensagem = $stmt->affected_rows > 0
                ? "Carro alterado com sucesso!"
                : "Nenhuma alteração realizada. Confira os dados e o ID.";
        } elseif ($stmt->errno == 1062) {
            $mensagem = "Esta placa já está cadastrada para outro carro.";
        } else {
            $mensagem = "Erro ao alterar carro: " . $stmt->error;
        }

        $stmt->close();

        $registro = buscarCarro($conexao, $id);
        if ($registro) {
            $campos = $registro;
        }
    }
}

elseif (isset($_POST["excluir"])) {
    if ($id > 0) {
        $stmt = $conexao->prepare(
            "DELETE FROM carros WHERE idcarro = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $mensagem = "Carro excluído com sucesso!";
            $campos = array_fill_keys(array_keys($campos), "");
        } else {
            $mensagem = "Carro não encontrado.";
        }

        $stmt->close();
    } else {
        $mensagem = "Informe o ID do carro para excluir.";
    }
}

elseif (isset($_POST["voltar"]) || isset($_POST["avancar"])) {
    $operador = isset($_POST["avancar"]) ? ">" : "<";
    $ordem = isset($_POST["avancar"]) ? "ASC" : "DESC";

    if ($id > 0) {
        $sql = "SELECT * FROM carros
                WHERE idcarro $operador ?
                ORDER BY idcarro $ordem LIMIT 1";

        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id);
    } else {
        $sql = "SELECT * FROM carros
                ORDER BY idcarro $ordem LIMIT 1";

        $stmt = $conexao->prepare($sql);
    }

    $stmt->execute();
    $registro = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($registro) {
        $campos = $registro;
        $mensagem = "Registro carregado!";
    } else {
        $mensagem = "Não existe outro carro nessa direção.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Carros</title>
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
<form action="carro.php" method="post">

    <h2>Gerenciar Carros</h2>
    <p><?= hCarro($mensagem) ?></p>

    <div class="campo">
        <label for="idcarro">ID do Carro</label>
        <input type="number" id="idcarro" name="idcarro"
               value="<?= hCarro($campos['idcarro']) ?>">
    </div>

    <div class="campo">
        <label for="marca">Marca</label>
        <input type="text" id="marca" name="marca" maxlength="30"
               value="<?= hCarro($campos['marca']) ?>" required>
    </div>

    <div class="campo">
        <label for="modelo">Modelo</label>
        <input type="text" id="modelo" name="modelo" maxlength="30"
               value="<?= hCarro($campos['modelo']) ?>" required>
    </div>

    <div class="campo">
        <label for="anofabricacao">Ano de Fabricação</label>
        <input type="number" id="anofabricacao" name="anofabricacao"
               min="1901" max="<?= (int)date('Y') + 1 ?>"
               value="<?= hCarro($campos['anofabricacao']) ?>" required>
    </div>

    <div class="campo">
        <label for="cor">Cor</label>
        <input type="text" id="cor" name="cor" maxlength="15"
               value="<?= hCarro($campos['cor']) ?>" required>
    </div>

    <div class="campo">
        <label for="placa">Placa</label>
        <input type="text" id="placa" name="placa"
               maxlength="7" minlength="7"
               value="<?= hCarro($campos['placa']) ?>" required>
    </div>

    <div class="campo">
        <label for="km">Quilometragem</label>
        <input type="number" id="km" name="km" min="0"
               value="<?= hCarro($campos['km']) ?>">
    </div>

    <div class="campo">
        <label for="preco">Preço (R$)</label>
        <input type="number" id="preco" name="preco"
               min="0" step="0.01"
               value="<?= hCarro($campos['preco']) ?>" required>
    </div>

    <div class="campo">
        <label for="situacao">Situação do Veículo</label>
        <input type="text" id="situacao" name="situacao"
               maxlength="10"
               value="<?= hCarro($campos['situacao']) ?>" required>
    </div>

    <button type="submit" name="submit">✏️ Cadastrar</button>
    <button type="submit" name="consultar">📄 Consultar</button>
    <button type="submit" name="alterar">📝 Alterar</button>
    <button type="submit" name="excluir"
            onclick="return confirm('Deseja excluir este carro?')">
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
