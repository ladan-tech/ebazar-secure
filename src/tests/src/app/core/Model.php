<?php
defined('EBAZAR') or exit;

/**
 * Classe de base des modèles : donne accès à la base de données.
 * Toutes les requêtes des modèles sont des requêtes préparées (anti-injection SQL).
 */
abstract class Model
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connexion();
    }

    protected function requete(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $cle => $valeur) {
            $type = is_int($valeur) ? PDO::PARAM_INT : (is_bool($valeur) ? PDO::PARAM_BOOL : (is_null($valeur) ? PDO::PARAM_NULL : PDO::PARAM_STR));
            $stmt->bindValue(is_int($cle) ? $cle + 1 : ':' . ltrim($cle, ':'), $valeur, $type);
        }
        $stmt->execute();
        return $stmt;
    }
}
