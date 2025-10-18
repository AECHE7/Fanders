<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    public function change(): void
    {
        // users
        if (!$this->table('users')->exists()) {
            $this->table('users')
                ->addColumn('name', 'string', ['limit' => 100])
                ->addColumn('email', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('password', 'string', ['limit' => 255])
                ->addColumn('role', 'string', ['limit' => 20])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active'])
                ->addColumn('last_login', 'timestamp', ['null' => true, 'default' => null])
                ->addColumn('phone_number', 'string', ['limit' => 20, 'null' => true])
                ->addColumn('password_changed_at', 'timestamp', ['null' => true, 'default' => null])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['email'], ['unique' => true])
                ->addIndex(['phone_number'], ['unique' => true])
                ->create();
        }

        // clients
        if (!$this->table('clients')->exists()) {
            $this->table('clients')
                ->addColumn('name', 'string', ['limit' => 100])
                ->addColumn('email', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('phone_number', 'string', ['limit' => 20])
                ->addColumn('address', 'string', ['limit' => 255])
                ->addColumn('identification_type', 'string', ['limit' => 50, 'null' => true])
                ->addColumn('identification_number', 'string', ['limit' => 100, 'null' => true])
                ->addColumn('date_of_birth', 'date', ['null' => true])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'active'])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['email'], ['unique' => true])
                ->addIndex(['phone_number'], ['unique' => true])
                ->addIndex(['identification_number'], ['unique' => true])
                ->create();
        }

        // loans
        if (!$this->table('loans')->exists()) {
            $this->table('loans')
                ->addColumn('client_id', 'integer')
                ->addColumn('principal', 'decimal', ['precision' => 10, 'scale' => 2])
                ->addColumn('interest_rate', 'decimal', ['precision' => 5, 'scale' => 4])
                ->addColumn('term_weeks', 'integer')
                ->addColumn('total_interest', 'decimal', ['precision' => 10, 'scale' => 2])
                ->addColumn('insurance_fee', 'decimal', ['precision' => 10, 'scale' => 2])
                ->addColumn('total_loan_amount', 'decimal', ['precision' => 10, 'scale' => 2])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'Application'])
                ->addColumn('application_date', 'timestamp')
                ->addColumn('approval_date', 'timestamp', ['null' => true, 'default' => null])
                ->addColumn('disbursement_date', 'timestamp', ['null' => true, 'default' => null])
                ->addColumn('completion_date', 'timestamp', ['null' => true, 'default' => null])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['client_id'])
                ->create()
                ->addForeignKey('client_id', 'clients', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE']);
        }

        // payments
        if (!$this->table('payments')->exists()) {
            $this->table('payments')
                ->addColumn('loan_id', 'integer')
                ->addColumn('user_id', 'integer')
                ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
                ->addColumn('payment_date', 'timestamp')
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['loan_id'])
                ->addIndex(['user_id'])
                ->create()
                ->addForeignKey('loan_id', 'loans', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
                ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE']);
        }

        // cash_blotter
        if (!$this->table('cash_blotter')->exists()) {
            $this->table('cash_blotter')
                ->addColumn('blotter_date', 'date')
                ->addColumn('total_inflow', 'decimal', ['precision' => 10, 'scale' => 2, 'default'=>0])
                ->addColumn('total_outflow', 'decimal', ['precision' => 10, 'scale' => 2, 'default'=>0])
                ->addColumn('calculated_balance', 'decimal', ['precision' => 10, 'scale' => 2, 'default'=>0])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['blotter_date'], ['unique' => true])
                ->create();
        }

        // transactions
        if (!$this->table('transactions')->exists()) {
            $this->table('transactions')
                ->addColumn('user_id', 'integer')
                ->addColumn('transaction_type', 'string', ['limit' => 50])
                ->addColumn('reference_id', 'integer', ['null' => true])
                ->addColumn('details', 'text', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['user_id'])
                ->create()
                ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE']);
        }

        // transaction_logs
        if (!$this->table('transaction_logs')->exists()) {
            $this->table('transaction_logs')
                ->addColumn('entity_type', 'string', ['limit' => 50])
                ->addColumn('entity_id', 'integer', ['null' => true])
                ->addColumn('action', 'string', ['limit' => 50])
                ->addColumn('user_id', 'integer', ['null' => true])
                ->addColumn('details', 'text', ['null' => true])
                ->addColumn('timestamp', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                ->addIndex(['user_id'])
                ->create()
                ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'SET_NULL', 'update'=> 'CASCADE']);
        }
    }
}
