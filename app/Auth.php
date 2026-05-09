<?php
class Auth
{
    private Database $db;
    private array $config;

    public function __construct(Database $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function ensureInitialAdmin(): void
    {
        $email = $this->config['admin_email'];
        $existing = $this->db->one('SELECT id FROM usuarios WHERE email = ?', 's', [$email]);

        if ($existing) {
            return;
        }

        $hash = password_hash($this->config['admin_password'], PASSWORD_DEFAULT);

        $userId = $this->db->execute(
            "INSERT INTO usuarios (email, senha_hash, tipo, status) VALUES (?, ?, 'admin', 'ativo')",
            'ss',
            [$email, $hash]
        );

        $empresas = $this->db->all('SELECT id FROM empresas');

        foreach ($empresas as $empresa) {
            $this->db->execute(
                'INSERT IGNORE INTO usuario_empresas (usuario_id, empresa_id) VALUES (?, ?)',
                'ii',
                [$userId, $empresa['id']]
            );
        }
    }

    public function login(string $email, string $password): array
    {
        $user = $this->db->one('SELECT * FROM usuarios WHERE email = ?', 's', [$email]);

        if (!$user || !password_verify($password, $user['senha_hash'])) {
            return ['ok' => false, 'message' => 'E-mail ou senha inválidos.'];
        }

        if ($user['status'] !== 'ativo') {
            return ['ok' => false, 'message' => 'Sua conta ainda não foi aprovada pelo admin.'];
        }

        $_SESSION['usuario_id'] = $user['id'];

        return ['ok' => true, 'message' => 'Login realizado.'];
    }

    public function register(string $email, string $password): array
    {
        if ($email === '' || $password === '') {
            return ['ok' => false, 'message' => 'Preencha o e-mail e a senha pessoal.'];
        }

        $exists = $this->db->one('SELECT id FROM usuarios WHERE email = ?', 's', [$email]);

        if ($exists) {
            return ['ok' => false, 'message' => 'Este e-mail já possui uma conta.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->execute(
            "INSERT INTO usuarios (email, senha_hash, tipo, status) VALUES (?, ?, 'usuario', 'pendente')",
            'ss',
            [$email, $hash]
        );

        return ['ok' => true, 'message' => 'Conta criada com sucesso. Aguarde o admin aprovar seu acesso.'];
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function user(): ?array
    {
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return $this->db->one('SELECT * FROM usuarios WHERE id = ?', 'i', [$_SESSION['usuario_id']]);
    }

    public function isAdmin(?array $user): bool
    {
        return $user && $user['tipo'] === 'admin';
    }
}
