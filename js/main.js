/**
 * Plik JavaScript: main.js
 * Użycie zmiennych, pętli, tablic, funkcji, obiektów wbudowanych i własnych.
 * Dodatkowo logika do elementu HTML5 Canvas.
 */

function getVisibleElementById(id) {
    const matches = document.querySelectorAll('[id="' + id + '"]');
    for (const el of matches) {
        if (el && el.offsetParent !== null) return el;
    }
    return matches[0] || null;
}

// 1. OBIEKT WŁASNY: BookingManager - Zarządza logiką rezerwacji
const BookingManager = {
    // Tablica (Array) godzin otwarcia
    dostepneGodziny: ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
    
    // Metoda własna obiektu do generowania godzin
    generujGodziny: function(wybranaData) {
        // Obiekt wbudowany: Date
        const dataObj = new Date(wybranaData);
        // Obiekt wbudowany w JS: document
        const timeSelect = getVisibleElementById('time');
        
        // Zabezpieczenie przed błędem, jeśli to nie jest strona book.php
        if (!timeSelect) return;
        
        // Czyszczenie selecta przed dodaniem nowych opcji
        timeSelect.innerHTML = '<option value="" disabled selected>Wybierz godzinę...</option>';
        
        // Sprawdzenie, czy wybrana data nie jest z przeszłości
        const dzisiaj = new Date();
        dzisiaj.setHours(0,0,0,0);
        
        if (dataObj < dzisiaj) {
            alert('Wybrano datę z przeszłości. Proszę wybrać poprawną datę.');
            timeSelect.disabled = true;
            return;
        }

        // Symulacja: w weekendy salon czynny krócej
        // 0 = Niedziela, 6 = Sobota
        const dzienTygodnia = dataObj.getDay();
        let godzinyDoWyswietlenia = [];

        if (dzienTygodnia === 0) {
            alert('W niedzielę salon jest nieczynny.');
            timeSelect.disabled = true;
            return;
        } else if (dzienTygodnia === 6) {
            // Użycie pętli for do przefiltrowania godzin dla soboty
            for (let i = 0; i < this.dostepneGodziny.length; i++) {
                if (parseInt(this.dostepneGodziny[i]) <= 14) {
                    godzinyDoWyswietlenia.push(this.dostepneGodziny[i]);
                }
            }
        } else {
            // Kopia tablicy dla zwykłego dnia
            godzinyDoWyswietlenia = [...this.dostepneGodziny];
        }

        // Pętla do tworzenia opcji HTML
        godzinyDoWyswietlenia.forEach(function(godzina) {
            const option = document.createElement('option');
            option.value = godzina;
            option.textContent = godzina;
            timeSelect.appendChild(option);
        });

        // Włączenie selecta
        timeSelect.disabled = false;
    },

    // Walidacja formularza przed wysłaniem
    inicjujWalidacje: function() {
        const formularz = getVisibleElementById('bookingForm');
        if (formularz) {
            formularz.addEventListener('submit', function(event) {
                const name = (getVisibleElementById('name') || {}).value || '';
                const date = (getVisibleElementById('date') || {}).value || '';
                const time = (getVisibleElementById('time') || {}).value || '';

                if (name.length < 3) {
                    alert('Imię i nazwisko musi mieć co najmniej 3 znaki.');
                    event.preventDefault(); // Zatrzymanie wysyłania formularza
                }
                
                if (!date || !time) {
                    alert('Musisz wybrać datę i godzinę wizyty.');
                    event.preventDefault();
                }
            });
        }
    }
};

// 2. OBIEKT WŁASNY: CanvasChart - do obsługi elementu HTML5 Canvas
const CanvasChart = {
    rysujWykres: function() {
        const canvas = getVisibleElementById('ratingCanvas');
        if (!canvas) return; // Zabezpieczenie dla innych podstron

        const ctx = canvas.getContext('2d');
        const srodekX = canvas.width / 2;
        const srodekY = canvas.height / 2;
        const promien = 100;
        
        // Pobranie średniej oceny z atrybutu HTML (data-average)
        let srednia = parseFloat(canvas.getAttribute('data-average'));
        if (isNaN(srednia)) srednia = 0;

        // Obliczenie procentu z maksymalnej oceny (5)
        const procent = srednia / 5;
        // Kąt w radianach (od -0.5 PI do 1.5 PI to pełny obrót)
        const katKoncowy = (-0.5 * Math.PI) + (procent * 2 * Math.PI);

        // Rysowanie tła wykresu (szare koło)
        ctx.beginPath();
        ctx.arc(srodekX, srodekY, promien, 0, 2 * Math.PI);
        ctx.lineWidth = 20;
        ctx.strokeStyle = '#e0e0e0';
        ctx.stroke();

        // Rysowanie wykresu właściwego (złoty łuk proporcjonalny do oceny)
        if (srednia > 0) {
            ctx.beginPath();
            ctx.arc(srodekX, srodekY, promien, -0.5 * Math.PI, katKoncowy);
            ctx.lineWidth = 20;
            // Kolor z CSS (Złoty: #d4af37)
            ctx.strokeStyle = '#d4af37';
            // Styl zakończenia łuku
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        // Tekst na środku koła
        ctx.font = 'bold 30px Arial';
        ctx.fillStyle = '#1a1a1a';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(srednia.toFixed(1), srodekX, srodekY);
    }
};

// 3. OBIEKT WŁASNY: SignaturePad - Canvas do rysowania podpisu
const SignaturePad = {
    inicjuj: function() {
        const canvas = getVisibleElementById('signatureCanvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        let isDrawing = false;
        
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#1a1a1a';

        // Obliczanie pozycji myszy/dotyku względem płótna
        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            // Skalowanie współrzędnych ze względu na responsywność CSS
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;

            let clientX = e.clientX;
            let clientY = e.clientY;

            // Obsługa ekranów dotykowych
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }

            return {
                x: (clientX - rect.left) * scaleX,
                y: (clientY - rect.top) * scaleY
            };
        }

        function startPosition(e) {
            isDrawing = true;
            draw(e);
        }

        function endPosition() {
            isDrawing = false;
            ctx.beginPath();
        }

        function draw(e) {
            if (!isDrawing) return;
            // Zapobieganie przewijaniu strony podczas rysowania na ekranie dotykowym
            if (e.type.includes('touch')) e.preventDefault(); 
            
            const pos = getMousePos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        // Eventy dla myszy
        canvas.addEventListener('mousedown', startPosition);
        canvas.addEventListener('mouseup', endPosition);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseout', endPosition);

        // Eventy dla ekranów dotykowych (np. tablety, telefony)
        canvas.addEventListener('touchstart', startPosition, {passive: false});
        canvas.addEventListener('touchend', endPosition);
        canvas.addEventListener('touchmove', draw, {passive: false});

        // Obsługa przycisku "Wyczyść"
        const clearBtn = getVisibleElementById('clearSignature');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            });
        }
    }
};

// 4. OBIEKT WŁASNY: MobileMenu - obsługa menu mobilnego
const MobileMenu = {
    init: function() {
        const openBtn = document.querySelector('[data-mobile-menu-open]');
        const menu = document.querySelector('[data-mobile-menu]');
        const closeBtn = document.querySelector('[data-mobile-menu-close]');
        if (!openBtn || !menu || !closeBtn) return;

        const close = () => {
            menu.classList.remove('is-open');
            openBtn.setAttribute('aria-expanded', 'false');
        };

        const open = () => {
            menu.classList.add('is-open');
            openBtn.setAttribute('aria-expanded', 'true');
        };

        openBtn.addEventListener('click', open);
        closeBtn.addEventListener('click', close);
        menu.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    }
};

// 5. EVENT LISTENERS (Inicjalizacja)
document.addEventListener('DOMContentLoaded', function() {
    
    // Obsługa wyboru daty
    const dateInput = getVisibleElementById('date');
    if (dateInput) {
        // Obiekt wbudowany Date użyty do ustawienia min atrybutu
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);

        dateInput.addEventListener('change', function(e) {
            BookingManager.generujGodziny(e.target.value);
        });
    }

    // Uruchomienie metod obiektów
    BookingManager.inicjujWalidacje();
    CanvasChart.rysujWykres();
    SignaturePad.inicjuj();
    MobileMenu.init();
});
