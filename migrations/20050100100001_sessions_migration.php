<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SessionsMigration extends AbstractMigration
{
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() == 'mysql') {
            $this->query('CREATE TABLE `sessions` (
    `sess_id` VARBINARY(128) NOT NULL PRIMARY KEY,
    `sess_data` BLOB NOT NULL,
    `sess_lifetime` INTEGER UNSIGNED NOT NULL,
    `sess_time` INTEGER UNSIGNED NOT NULL,
    INDEX `sessions_sess_lifetime_idx` (`sess_lifetime`)
) COLLATE utf8mb4_bin, ENGINE = InnoDB;');
        } elseif ($this->getAdapter()->getAdapterType() === 'pgsql') {
            $this->query("CREATE TABLE sessions (
    sess_id VARCHAR(128) NOT NULL PRIMARY KEY,
    sess_data BYTEA NOT NULL,
    sess_time INTEGER NOT NULL,
    sess_lifetime INTEGER NOT NULL
);");
        } elseif ($this->getAdapter()->getAdapterType() === 'mssql') {
            $this->query("CREATE TABLE [dbo].[sessions](
    [sess_id] [nvarchar](255) NOT NULL,
    [sess_data] [ntext] NOT NULL,
    [sess_time] [int] NOT NULL,
    [sess_lifetime] [int] NOT NULL,
    PRIMARY KEY CLUSTERED(
        [sess_id] ASC
    ) WITH (
        PAD_INDEX  = OFF,
        STATISTICS_NORECOMPUTE  = OFF,
        IGNORE_DUP_KEY = OFF,
        ALLOW_ROW_LOCKS  = ON,
        ALLOW_PAGE_LOCKS  = ON
    ) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]");
        } else {
            throw new RuntimeException('Unsupported database adapter for sessions.');
        }
    }

    public function down(): void
    {
        $this->table('sessions')->drop()->update();
    }
}
