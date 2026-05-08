from pathlib import Path
import zipfile, shutil

# Vou usar a versão anterior limpa como base e tornar compatível com PHP mais antigo.
source = Path("/mnt/data/admin_dashboard_php_limpo/index.php")
if not source.exists():
    source = Path("/mnt/data/parisviu_admin_login_php/index.php")

php = source.read_text(encoding="utf-8")

# Compatibilidade PHP:
# - Remover mb_strtoupper, que pode falhar se mbstring não estiver habilitado.
# - Remover str_contains, que só existe no PHP 8.
# - Remover arrow function fn(), que pode falhar em PHP < 7.4.
php = php.replace(
"""function creative_type($name) {
    $upper = mb_strtoupper((string)$name, 'UTF-8');
    if (str_contains($upper, 'CARROSSEL')) return 'CARROSSEL';
    if (str_contains($upper, 'VÍDEO') || str_contains($upper, 'VIDEO')) return 'VÍDEO';
    return 'CRIATIVO';
}""",
"""function creative_type($name) {
    $upper = strtoupper((string)$name);

    if (strpos($upper, 'CARROSSEL') !== false) {
        return 'CARROSSEL';
    }

    if (strpos($upper, 'VÍDEO') !== false || strpos($upper, 'VIDEO') !== false) {
        return 'VÍDEO';
    }

    return 'CRIATIVO';
}"""
)

php = php.replace(
"<?= array_sum(array_map(fn($company) => count($company['campaigns']), $companies)) ?>",
"<?php $totalCampaignsCount = 0; foreach ($companies as $companyItem) { $totalCampaignsCount += count($companyItem['campaigns']); } echo e($totalCampaignsCount); ?>"
)

# Também evitar count em variável possivelmente nula em versões antigas? Não deve ocorrer, mas deixamos robusto.
php = php.replace(
"$maxReach = max(array_column($allCreatives, 'reach'));",
"$maxReach = count($allCreatives) ? max(array_column($allCreatives, 'reach')) : 1;"
)
php = php.replace(
"$maxSpend = max(array_column($allCreatives, 'spend'));",
"$maxSpend = count($allCreatives) ? max(array_column($allCreatives, 'spend')) : 1;"
)

# Criar versão com erros exibíveis opcionalmente comentados no topo para debug.
# Não vamos habilitar display_errors por padrão para produção, mas adicionamos comentários.
php = php.replace(
"<?php\nsession_start();",
"""<?php
// Para depuração temporária, descomente as 3 linhas abaixo:
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();"""
)

# Garantir que não há resíduos Python.
bad_markers = ["from pathlib import Path", "import zipfile", "shutil.rmtree", "base.mkdir", "python_user_visible"]
found = [m for m in bad_markers if m in php]
if found:
    raise ValueError(f"Resíduos indesejados encontrados: {found}")

# Criar pasta e zip
base = Path("/mnt/data/admin_dashboard_php_corrigido_erro500")
if base.exists():
    shutil.rmtree(base)
base.mkdir()

index_path = base / "index.php"
index_path.write_text(php, encoding="utf-8")

readme = """# Admin Dashboard PHP corrigido

Esta versão foi revisada para evitar erro HTTP 500 em hospedagens com PHP mais antigo ou sem algumas extensões.

Correções feitas:
- Removido `str_contains()`, que exige PHP 8.
- Removido `mb_strtoupper()`, que pode falhar sem extensão mbstring.
- Removido `fn()`, que pode falhar em versões antigas.
- Mantido login de admin.

Login:
E-mail: kevinnikolas417@gmail.com
Senha: 123456

Uso:
Envie apenas o `index.php` para a raiz do repositório GitHub.
"""
(base / "README.md").write_text(readme, encoding="utf-8")

zip_path = Path("/mnt/data/admin_dashboard_php_corrigido_erro500.zip")
if zip_path.exists():
    zip_path.unlink()

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as z:
    z.write(index_path, arcname="index.php")
    z.write(base / "README.md", arcname="README.md")

print(f"Arquivo corrigido criado: {zip_path}")
print(f"index.php corrigido: {index_path}")
