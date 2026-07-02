/**
 * useBluetoothPrinter.js
 * Composable Vue — Impression Bluetooth ESC/POS
 * Compatible GIGA360 80mm + tout thermal BT ESC/POS
 *
 * Emplacement : resources/js/composables/useBluetoothPrinter.js
 */

import { ref } from 'vue'

// UUID ESC/POS standard (Serial Port Profile over BLE)
// Si GIGA360 ne répond pas, essayer avec acceptAllDevices: true en debug
const SERVICE_UUID        = '000018f0-0000-1000-8000-00805f9b34fb'
const CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb'

export function useBluetoothPrinter() {
    const connecte      = ref(false)
    const connexionEnCours = ref(false)
    const erreur        = ref(null)

    let characteristic  = null

    // ─── Détection PWA standalone ─────────────────────────────────────────
    const estPWA = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true

    // ─── Bluetooth disponible sur cet appareil ? ─────────────────────────
    const bluetoothDisponible = () =>
        typeof navigator !== 'undefined' &&
        typeof navigator.bluetooth !== 'undefined'

    // ─── Connexion à l'imprimante ─────────────────────────────────────────
    const connecter = async () => {
        if (!bluetoothDisponible()) {
            erreur.value = 'Bluetooth non disponible sur cet appareil ou navigateur.'
            return false
        }

        try {
            connexionEnCours.value = true
            erreur.value = null

            const device = await navigator.bluetooth.requestDevice({
                // Chercher par nom d'abord ; si ça échoue, commenter et utiliser acceptAllDevices
                filters: [
                    { namePrefix: 'GIGA' },
                    { namePrefix: 'RPP' },      // variante courante imprimantes 80mm
                    { namePrefix: 'Printer' },
                    { namePrefix: 'MTP' },
                ],
                optionalServices: [SERVICE_UUID],
            })

            const server  = await device.gatt.connect()
            const service = await server.getPrimaryService(SERVICE_UUID)
            characteristic = await service.getCharacteristic(CHARACTERISTIC_UUID)

            connecte.value = true

            // Gérer la déconnexion physique
            device.addEventListener('gattserverdisconnected', () => {
                connecte.value = false
                characteristic  = null
            })

            return true
        } catch (e) {
            erreur.value = e.message || 'Erreur de connexion Bluetooth.'
            return false
        } finally {
            connexionEnCours.value = false
        }
    }

    // ─── Écrire des octets (par chunks de 512 — limite BLE) ──────────────
    const ecrire = async (bytes) => {
        if (!characteristic) throw new Error('Imprimante non connectée.')

        const CHUNK = 512
        for (let i = 0; i < bytes.length; i += CHUNK) {
            await characteristic.writeValue(bytes.slice(i, i + CHUNK))
            // Petit délai entre chunks pour les imprimantes lentes
            await new Promise(r => setTimeout(r, 30))
        }
    }

    // ─── Encoder texte en CP1252 (Latin-1 approx. pour ESC/POS) ─────────
    const encoder = new TextEncoder() // UTF-8 par défaut

    const encoderTexte = (texte) => encoder.encode(texte)

    // ─── Commandes ESC/POS ────────────────────────────────────────────────
    const ESC = 0x1B
    const GS  = 0x1D
    const LF  = 0x0A

    const CMD = {
        init:          new Uint8Array([ESC, 0x40]),           // Initialiser
        centrer:       new Uint8Array([ESC, 0x61, 0x01]),     // Alignement centre
        gauche:        new Uint8Array([ESC, 0x61, 0x00]),     // Alignement gauche
        gras_on:       new Uint8Array([ESC, 0x45, 0x01]),     // Gras actif
        gras_off:      new Uint8Array([ESC, 0x45, 0x00]),     // Gras inactif
        grand_on:      new Uint8Array([ESC, 0x21, 0x30]),     // Double hauteur+largeur
        grand_off:     new Uint8Array([ESC, 0x21, 0x00]),     // Taille normale
        couper:        new Uint8Array([GS,  0x56, 0x41, 0x10]), // Coupe partielle
        ligne:         encoderTexte('--------------------------------\n'),
        ligne_double:  encoderTexte('================================\n'),
        saut:          new Uint8Array([LF]),
    }

    const concat = (...parts) => {
        const totalLength = parts.reduce((sum, p) => sum + p.length, 0)
        const result = new Uint8Array(totalLength)
        let offset = 0
        for (const p of parts) {
            result.set(p, offset)
            offset += p.length
        }
        return result
    }

    // ─── Imprimer un ticket caissier ──────────────────────────────────────
    //
    // ticket = {
    //   numero: Number,
    //   date_emission: String,   // 'YYYY-MM-DD'
    //   emis_le: String,         // ISO datetime
    //   prix_paye: Number,
    //   ticket_type: { libelle: String },
    //   user: { name: String },
    //   tenant: { nom: String },  // optionnel
    // }
    const imprimerTicket = async (ticket) => {
        if (!connecte.value) {
            const ok = await connecter()
            if (!ok) return false
        }

        const numero = String(ticket.numero).padStart(4, '0')
        const date   = new Date(ticket.date_emission).toLocaleDateString('fr-FR')
        const heure  = new Date(ticket.emis_le).toLocaleTimeString('fr-FR', {
            hour: '2-digit', minute: '2-digit',
        })
        const montant = new Intl.NumberFormat('fr-FR').format(ticket.prix_paye) + ' FCFA'
        const nomPoste = ticket.tenant?.nom || 'POSTE DE SANTE DE BOULAL'

        try {
            const data = concat(
                CMD.init,

                // En-tête
                CMD.centrer,
                CMD.gras_on,
                encoderTexte(nomPoste + '\n'),
                CMD.gras_off,
                encoderTexte('RECU DE PAIEMENT\n'),
                CMD.ligne_double,

                // Numéro de ticket (grand)
                CMD.centrer,
                CMD.grand_on,
                encoderTexte(`N  ${numero}\n`),
                CMD.grand_off,
                CMD.saut,

                // Détails
                CMD.gauche,
                CMD.ligne,
                encoderTexte(`Date    : ${date}\n`),
                encoderTexte(`Heure   : ${heure}\n`),
                encoderTexte(`Caissier: ${ticket.user.name}\n`),
                CMD.ligne,

                // Prestation
                CMD.gras_on,
                encoderTexte(`${ticket.ticket_type.libelle}\n`),
                CMD.gras_off,
                CMD.ligne,

                // Montant (grand)
                CMD.centrer,
                CMD.grand_on,
                CMD.gras_on,
                encoderTexte(`${montant}\n`),
                CMD.gras_off,
                CMD.grand_off,

                // Pied
                CMD.ligne_double,
                CMD.centrer,
                encoderTexte('Merci - Bonne sante\n'),
                encoderTexte('Conservez ce recu\n'),
                new Uint8Array([LF, LF, LF]),

                // Coupe
                CMD.couper,
            )

            await ecrire(data)
            return true
        } catch (e) {
            erreur.value = e.message
            return false
        }
    }

    return {
        connecte,
        connexionEnCours,
        erreur,
        estPWA,
        bluetoothDisponible,
        connecter,
        imprimerTicket,
    }
}
