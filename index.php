from pathlib import Path
import zipfile, shutil

# Usar o index.php limpo já gerado anteriormente, se existir.
source = Path("/mnt/data/parisviu_admin_login_php/index.php")

# Se por algum motivo não existir, tentar usar a versão do ZIP anterior extraída não é necessário aqui.
# Vamos validar que o arquivo não contém o texto Python que apareceu no print.
if not source.exists():
    raise FileNotFoundError("Arquivo PHP limpo não encontrado. Gere novamente a versão admin antes de continuar.")

php = source.read_text(encoding="utf-8")

# Remover qualquer resíduo acidental de código Python, caso tenha sido colado dentro do arquivo.
bad_markers = [
    "from pathlib import Path",
    "import zipfile",
    "shutil.rmtree",
    "base.mkdir",
    "zipfile.ZipFile",
    "print(f\"Arquivo criado",
    "python",
]

# Se encontrar algum marcador fora de texto normal, vamos abortar para evitar entregar arquivo contaminado.
found = [marker for marker in bad_markers if marker in php]
if found:
    raise ValueError(f"O arquivo fonte contém resíduos indesejados: {found}")

# Garantir início correto do PHP.
php = php.lstrip()
if not php.startswith("<?php"):
    raise ValueError("O arquivo não começa com <?php.")

# Criar pasta limpa.
base = Path("/mnt/data/admin_dashboard_php_limpo")
if base.exists():
    shutil.rmtree(base)
base.mkdir()

clean_index = base / "index.php"
clean_index.write_text(php, encoding="utf-8")

readme = """# Admin Dashboard PHP limpo

Envie o arquivo `index.php` diretamente para a raiz do repositório GitHub.

Não envie códigos Python.
Não envie o conteúdo do ZIP colado no editor.
Não envie o arquivo ZIP como site final.

Login:
E-mail: kevinnikolas417@gmail.com
Senha: 123456
"""

(base / "README.md").write_text(readme, encoding="utf-8")

zip_path = Path("/mnt/data/admin_dashboard_php_limpo.zip")
if zip_path.exists():
    zip_path.unlink()

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
    z.write(clean_index, arcname="index.php")
    z.write(base / "README.md", arcname="README.md")

print(f"Arquivo limpo criado: {zip_path}")
print(f"index.php limpo: {clean_index}")
print("Validação: o arquivo começa com <?php e não contém código Python.")
