import SignaturePad from 'signature_pad'

// Unterschrift im Protokollformular: Zeichenflaeche, Zuruecksetzen und Uebergabe als PNG.
// Das Formular wird ueber data-signature-form erkannt, der Hinweistext fuer eine leere
// Unterschrift kommt uebersetzt aus data-please-sign.
const init = () => {
    const form = document.querySelector('[data-signature-form]')
    const canvas = document.getElementById('sig')

    if (!form || !canvas) {
        return
    }

    const pad = new SignaturePad(canvas, { minWidth: 1, maxWidth: 2.5 })

    const resize = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1)
        const rect = canvas.getBoundingClientRect()

        canvas.width = rect.width * ratio
        canvas.height = 240 * ratio
        canvas.getContext('2d').scale(ratio, ratio)
        pad.clear()
    }

    window.addEventListener('resize', resize)
    resize()

    document.getElementById('clear').addEventListener('click', () => pad.clear())

    form.addEventListener('submit', (event) => {
        if (pad.isEmpty()) {
            event.preventDefault()
            alert(form.dataset.pleaseSign)

            return
        }

        document.getElementById('signature_data').value = pad.toDataURL('image/png')
    })
}

// Module laufen nach dem Parsen der Seite; der Fall "schon geladen" ist trotzdem abgedeckt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
} else {
    init()
}
