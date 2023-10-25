<?php

namespace App\Utils;

final class Status
{
    /**
     * Identity Document
     */
    public const CNI = 'CNI';
    public const ATTESTATION = 'ATTESTATION';
    public const PASSPORT = 'PASSPORT';
    public const CARTE_CONSULAIRE = 'CARTE CONSULAIRE';

    /**
     * Sex
     */
    public const FEMININ = 'FEMININ';
    public const MASCULIN = 'MASCULIN';

    /**
     * Mode Paiement
     */
    public const CAISSE = 'CAISSE';
    public const VIREMENT = 'VIREMENT';
    public const CHEQUE = 'CHEQUE';

    /**
     * Etat Civil
     */
    public const CONCUBIN = 'CONCUBIN';
    public const CELIBATAIRE = 'CELIBARAIRE';
    public const DIVORCE = 'DIVORCE';
    public const MARIEE = 'MARIEE';
    public const SEPARE = 'SEPARE';
    public const VEUF = 'VEUF';

    /**
     * Diplome
     */
    public const BAC = 'BAC';
    public const LICENCE = 'LICENCE';
    public const MASTER_1 = 'MASTER 1';
    public const MASTER_2 = 'MASTER 2';
    public const DOCTORAL = 'DOCTORAL';

    /**
     * Niveau de formation
     */
    public const BTS = 'BTS (Bac +2)';
    public const MAITRISE = 'MAITRISE (Bac + 4)';
    public const Master = 'MASTER (Bac + 5)';

    /**
     * Type de contrat
     */
    public const CDD = 'CDD';
    public const  CDI = 'CDI';
    public const STAGE = 'STAGE';
    public const OCCASIONNEL = 'OCCASIONNEL';

    /**
     * Type Temps Contractuel
     */
    public const TEMPS_PLEIN = 'TEMPS PlEIN';
    public const TEMPS_PARTIEL = 'TEMPS_PARTIEL';
}