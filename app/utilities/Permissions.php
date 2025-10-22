<?php
/**
 * Permissions - Centralized role normalization and permission checks
 */

require_once __DIR__ . '/../models/UserModel.php';

class Permissions {
    /**
     * Normalize a role string to the canonical form used by the app.
     * - lowercase
     * - replace underscores with hyphens
     */
    public static function normalizeRole($role) {
        if (!is_string($role)) return '';
        $r = strtolower(trim($role));
        $r = str_replace('_', '-', $r);
        // Map any legacy aliases if needed in future
        return $r;
    }

    /**
     * Check whether a role is within a set of allowed roles, with normalization.
     * @param string $role current user role
     * @param array|string $allowedRoles allowed role(s)
     */
    public static function isAllowed($role, $allowedRoles) {
        $role = self::normalizeRole($role);
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        $allowed = array_map([self::class, 'normalizeRole'], $allowedRoles);
        return in_array($role, $allowed, true);
    }

    // --- Convenience role checks ---
    public static function isSuperAdmin($role) {
        return self::normalizeRole($role) === UserModel::$ROLE_SUPER_ADMIN;
    }

    public static function isAdmin($role) {
        return self::normalizeRole($role) === UserModel::$ROLE_ADMIN;
    }

    public static function isManager($role) {
        return self::normalizeRole($role) === UserModel::$ROLE_MANAGER;
    }

    public static function isCashier($role) {
        return self::normalizeRole($role) === UserModel::$ROLE_CASHIER;
    }

    public static function isAccountOfficer($role) {
        return self::normalizeRole($role) === UserModel::$ROLE_ACCOUNT_OFFICER;
    }

    public static function isClient($role) {
        return self::normalizeRole($role) === UserModel::$ROLE_CLIENT;
    }

    // --- Navigation permissions ---
    public static function canViewDashboard($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
            UserModel::$ROLE_CASHIER,
            UserModel::$ROLE_CLIENT,
        ]);
    }

    public static function canAccessLoans($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
            UserModel::$ROLE_CASHIER,
            UserModel::$ROLE_CLIENT,
        ]);
    }

    public static function canManageClients($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
            UserModel::$ROLE_CASHIER,
        ]);
    }

    public static function canAccessPayments($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
            UserModel::$ROLE_CASHIER,
            UserModel::$ROLE_CLIENT,
        ]);
    }

    public static function canAccessCollectionSheets($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
            UserModel::$ROLE_CASHIER,
        ]);
    }

    public static function canAccessCashBlotter($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_CASHIER,
        ]);
    }

    public static function canAccessSLRDocuments($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
        ]);
    }

    public static function canAccessReports($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
        ]);
    }

    public static function canAccessTransactions($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
        ]);
    }

    public static function canManageStaff($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
        ]);
    }

    // --- Special pages/actions ---
    public static function canViewLoanApprovals($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
        ]);
    }

    public static function canCreateLoan($role) {
        return self::isAllowed($role, [
            UserModel::$ROLE_SUPER_ADMIN,
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
        ]);
    }

    public static function canRecordPayment($role) {
        return self::isCashier($role);
    }

    // --- User management rules ---
    /**
     * Can the actor change the password of the target account?
     * - Super-admin can change any
     * - A user can change their own
     * - Non-super-admins cannot change super-admin's password
     */
    public static function canEditUserPassword($actorRole, $targetRole, $isSelf = false) {
        $actorRole = self::normalizeRole($actorRole);
        $targetRole = self::normalizeRole($targetRole);

        if ($isSelf) return true;
        if ($actorRole === UserModel::$ROLE_SUPER_ADMIN) return true;
        if ($targetRole === UserModel::$ROLE_SUPER_ADMIN) return false;
        // Admin/Manager can change staff passwords (non-super-admin)
        return in_array($actorRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_MANAGER], true);
    }
}
