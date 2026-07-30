<?php

declare(strict_types=1);

namespace App\Support\Domain\AtmModel;

/**
 * Definiciones estáticas del árbol de módulos de ATM Hyosung MX Series.
 *
 * La jerarquía vive en código PHP puro (no en BD) porque es fija por familia
 * de modelo, cambia muy raramente y tenerla en código permite tipado fuerte,
 * testabilidad y rendimiento.
 *
 * @see AtmModuleTreeRegistry — Combina módulos según el modelo de ATM
 */
final class AtmModuleTreeData
{
    /**
     * Módulos comunes a todos los modelos de ATM.
     *
     * @return AtmModuleNode[]
     */
    public static function commonModules(): array
    {
        return [
            // CE (Control Electronics)
            new AtmModuleNode(
                code: 'CE',
                label: 'Control Electronics',
                children: [
                    new AtmModuleNode(
                        code: 'PC_CORE',
                        label: 'PC Core',
                        components: ['Main Board', 'CPU', 'RAM', 'Storage (SSD/HDD)'],
                    ),
                    new AtmModuleNode(
                        code: 'POWER',
                        label: 'Power System',
                        components: ['Power Supply', 'Backup Battery'],
                    ),
                ],
            ),
            // User Interfaces
            new AtmModuleNode(
                code: 'UI',
                label: 'User Interfaces',
                children: [
                    new AtmModuleNode(
                        code: 'OPL',
                        label: 'OPL (Customer Panel)',
                        components: ['LCD Screen', 'Touch Panel', 'AD Card', 'OSD Card'],
                    ),
                    new AtmModuleNode(
                        code: 'SPL',
                        label: 'SPL (Supervisor Panel)',
                        components: ['Service LCD', 'Touch Panel'],
                    ),
                ],
            ),
            // Peripherals
            new AtmModuleNode(
                code: 'PERIPH',
                label: 'Peripherals',
                children: [
                    new AtmModuleNode(
                        code: 'CARD_READER',
                        label: 'Card Reader',
                        components: ['MCU/DIP (Chip/Banda)', 'Anti-Skimming Module'],
                    ),
                    new AtmModuleNode(
                        code: 'EPP',
                        label: 'EPP (Pin-Pad)',
                        components: ['Encrypted Keyboard', 'Privacy Pad'],
                    ),
                    new AtmModuleNode(
                        code: 'SPR',
                        label: 'SPR (Receipt Printer)',
                        components: ['TPH (Thermal Print Head)', 'Cutter', 'Presenter', 'Paper Sensors'],
                    ),
                    new AtmModuleNode(
                        code: 'ADDON',
                        label: 'Additional',
                        components: ['NFC Reader', 'Barcode Reader', 'Coin Hopper'],
                    ),
                ],
            ),
            // Security & Sensors
            new AtmModuleNode(
                code: 'SEC',
                label: 'Security & Sensors (SIU / PNC)',
                children: [
                    new AtmModuleNode(
                        code: 'CABINET',
                        label: 'Cabinet Monitoring',
                        components: ['Door Sensors (Safe, Upper, Rear)', 'Seismic Sensors', 'Heat Detectors'],
                    ),
                    new AtmModuleNode(
                        code: 'USER_GUIDE',
                        label: 'User Guide',
                        components: ['LED Collar (Flickers)', 'Module Indicators'],
                    ),
                ],
            ),
        ];
    }

    /**
     * Módulo BRM (Bill Recycler Module) para modelos Recycler.
     */
    public static function recyclerModules(): AtmModuleNode
    {
        return new AtmModuleNode(
            code: 'BRM',
            label: 'BRM (Bill Recycler Module)',
            children: [
                new AtmModuleNode(
                    code: 'BMU',
                    label: 'BMU (Upper Body)',
                    components: ['CSM (Customer Service Module)', 'BC (Bill Checker / Validador)', 'TSK (Temporary Stacker / Escrow)', 'Upper Transports'],
                ),
                new AtmModuleNode(
                    code: 'BMD',
                    label: 'BMD (Lower Body)',
                    components: ['Cassettes (Recycle, Utility, Reject/Retract)', 'M-TR (Mid Transport)', 'RBU Card'],
                ),
            ],
        );
    }

    /**
     * Módulo CDU (Cash Dispenser Unit) para modelos Dispenser.
     */
    public static function dispenserModules(): AtmModuleNode
    {
        return new AtmModuleNode(
            code: 'CDU',
            label: 'CDU (Cash Dispenser Unit)',
            children: [
                new AtmModuleNode(
                    code: 'MB',
                    label: 'MB (Main Body)',
                    components: ['Carriage (Carro)', 'Rotor', 'Retraction Solenoids'],
                ),
                new AtmModuleNode(
                    code: 'SF',
                    label: 'SF (Separator/Feeder)',
                    components: ['Separation Module', 'Pick-up Rollers'],
                ),
                new AtmModuleNode(
                    code: 'STORAGE',
                    label: 'Storage',
                    components: ['Cassette Box', 'Reject/Retract Box'],
                ),
            ],
        );
    }

    /**
     * Módulo Document Deposit (solo modelos Recycler).
     */
    public static function documentDepositModules(): AtmModuleNode
    {
        return new AtmModuleNode(
            code: 'DOC_DEPOSIT',
            label: 'Document Deposit',
            children: [
                new AtmModuleNode(
                    code: 'PROCESSING',
                    label: 'Processing',
                    components: ['Shutter (Obturador)', 'Bundle Separator', 'RECOG/CIS (Reconocimiento)', 'ALG (Alineador)', 'Escrow (Custodia Temporal)', 'Check Cassette'],
                ),
            ],
        );
    }
}
