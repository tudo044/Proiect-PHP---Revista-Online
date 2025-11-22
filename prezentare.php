<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prezentare Proiect - Revistă Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 1000px; }
        h1, h2, h3 { color: #343a40; }
        h2 { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-top: 40px; }
        h3 { margin-top: 30px; color: #495057; }
        img { 
            max-width: 100%; 
            height: auto; 
            border: 1px solid #ddd; 
            margin-top: 15px; 
            background-color: #fff;
            border-radius: 4px;
        }
        .lead { font-size: 1.15rem; }
    </style>
</head>
<body>
    <div class="container my-5 p-5 bg-white shadow-sm rounded">
        
        <h1 class="mb-3">Tema 1: Descriere Aplicație Web (Bază de Date)</h1>
        <p class="lead"><strong>Student:</strong> Tudorache Andrei Silviu</p>
        <p class="lead"><strong>Seria:</strong> 23</p>
        <p class="lead"><strong>Grupa:</strong> 234</p>
        <p class="lead"><strong>Proiect Ales:</strong> Revistă Online</p>

        <h2>1. Descrierea Arhitecturii Aplicației</h2>
        <p>Proiectul meu este o platformă web dinamică de tip „Revistă Online”. Scopul ei principal este publicarea de articole (știri) și gestionarea interacțiunilor utilizatorilor (comentarii, feedback).</p>
        
        <h3 class_mt-4>Roluri și Entități Principale</h3>
        <p>Arhitectura aplicației se bazează pe 3 roluri principale de utilizatori și 6 entități (tabele) în baza de date:</p>
        
        <h4>Rolurile Utilizatorilor:</h4>
        <ul>
            <li><strong>Cititor:</strong> Rolul implicit. Utilizatorul standard care se poate înregistra, loga, citi articole, posta comentarii și se poate abona la newsletter.</li>
            <li><strong>Reporter:</strong> Un rol cu permisiuni ridicate. Poate face tot ce face un Cititor, dar în plus poate crea articole noi și le poate modifica pe cele proprii.</li>
            <li><strong>Admin:</strong> Super-utilizator. Are control total (CRUD) asupra tuturor componentelor: poate șterge orice articol, poate șterge comentarii și poate administra utilizatorii.</li>
        </ul>
        
        <h4>Entitățile Bazei de Date (Tabele):</h4>
        <ul>
            <li><strong>user:</strong> Stochează datele de autentificare (email, parola hash-uită) și rolul (folosind un tip `ENUM`).</li>
            <li><strong>categorii:</strong> Stochează categoriile principale în care vor fi încadrate știrile (ex: Sport, Tehnologie).</li>
            <li><strong>stiri:</strong> Tabela centrală care conține articolele.</li>
            <li><strong>comentarii:</strong> Stochează mesajele lăsate de utilizatori la o anumită știre.</li>
            <li><strong>feedback:</strong> Gestionează sistemul de like/dislike pentru știri.</li>
            <li><strong>newsletter:</strong> O tabelă simplă care colectează email-urile vizitatorilor pentru abonare.</li>
        </ul>

        <h3 class="mt-4">Relațiile dintre Entități</h3>
        <p>Conexiunile din baza de date sunt esențiale pentru funcționarea aplicației. Am folosit chei străine (Foreign Keys) pentru a menține integritatea datelor:</p>
        
        <ul>
            <li><strong>Relație 1:M (User -> Stiri):</strong> Un `user` (cu rol 'reporter'/'admin') poate fi autorul mai multor `stiri`. Relația se face prin `stiri.id_autor` -> `user.id`.</li>
            <li><strong>Relație 1:M (Categorii -> Stiri):</strong> O `categorie` poate conține mai multe `stiri`. Relația se face prin `stiri.id_categorie` -> `categorii.id`.</li>
            <li><strong>Relație 1:M (User -> Comentarii):</strong> Un `user` poate lăsa mai multe `comentarii`. Relația se face prin `comentarii.id_user` -> `user.id`.</li>
            <li><strong>Relație 1:M (Stiri -> Comentarii):</strong> O `stire` poate avea mai multe `comentarii`. Relația se face prin `comentarii.id_stire` -> `stiri.id`.</li>
        </ul>
        <p>Similar, tabela `feedback` acționează ca o punte între `user` și `stiri`, asigurând (printr-o cheie unică) că un utilizator poate da un singur feedback per știre.</p>
        <p>Diagrama schematică a bazei de date (generată automat de MariaDB) ilustrează vizual aceste relații:</p>
        
        <img src="imagini/schema-bd.jpg" alt="Schema Bazei de Date" class="img-fluid">

        <h2>2. Descrierea Soluției de Implementare (Procese)</h2>
        <p>Soluția a fost implementată în PHP, folosind o bază de date MySQL. Am folosit o abordare procedurală, cu separarea logicii de conectare într-o clasă de tip Singleton (`Database.php`) pentru a asigura o singură conexiune PDO per cerere.</p>
        <p>Partea de securitate este gestionată prin folosirea **Sesiunilor PHP** (`$_SESSION`) pentru a reține starea de autentificare și rolul utilizatorului. Parolele sunt securizate (hash-uite) în baza de date folosind funcțiile `password_hash()` și `password_verify()` (algoritmul BCRYPT).</p>
        <p>Principalele procese ale aplicației sunt detaliate mai jos, sub formă de diagrame de secvență (UML):</p>
        
        <h3 class="mt-4">Proces 1: Înregistrare Utilizator Nou</h3>
        <p>Fluxul arată cum un vizitator creează un cont, cum parola este hash-uită și cum este validată existența email-ului (UNIQUE).</p>
        <img src="imagini/inregistrare utilizator nou.png" alt="Flow Inregistrare">

        <h3 class="mt-4">Proces 2: Autentificare Utilizator (Login)</h3>
        <p>Arată cum parola hash-uită din BD este comparată cu string-ul oferit de utilizator folosind `password_verify()` și cum este creată Sesiunea PHP.</p>
        <img src="imagini/login.png" alt="Flow Login">

        <h3 class="mt-4">Proces 3: Publicare Știre (CRUD - Create)</h3>
        <p>Demonstrează verificarea rolului. Doar un 'reporter' sau 'admin' poate accesa formularul și insera o știre nouă (cu `id_autor` preluat din Sesiune).</p>
        <img src="imagini/publicare_stire.png" alt="Flow Publicare">
        
        <h3 class="mt-4">Proces 4: Adăugare Comentariu</h3>
        <p>Arată cum doar utilizatorii logați pot posta comentarii.</p>
        <img src="imagini/adaugare_comentariu.png" alt="Flow Comentariu">

        <h3 class="mt-4">Proces 5: Citire Listă Știri (CRUD - Read)</h3>
        <p>Un flux "Read" standard, deschis oricărui vizitator, care arată cum `index.php` preia toate știrile din baza de date.</p>
        <img src="imagini/citire_stiri.png" alt="Flow Citire Stiri">

        <h3 class="mt-4">Proces 6: Modificare Știre (CRUD - Update)</h3>
        <p>Un flux complex de permisiuni: doar un 'admin' (poate edita orice) sau un 'reporter' (doar știrile proprii) pot accesa această funcție.</p>
        <img src="imagini/modificare_stire.png" alt="Flow Update">

        <h3 class="mt-4">Proces 7: Ștergere Știre (CRUD - Delete)</h3>
        <p>Demonstrează cum permisiunea de ștergere este rezervată exclusiv rolului de 'admin'.</p>
        <img src="imagini/stergere_stire.png" alt="Flow Stergere">
        
        <h3 class="mt-4">Proces 8: Acordare Feedback (Like/Dislike)</h3>
        <p>Arată logica de a verifica dacă un vot există deja. Dacă da, votul este anulat (DELETE). Dacă nu, este adăugat (INSERT).</p>
        <img src="imagini/feedback.png" alt="Flow Feedback">

        <h3 class="mt-4">Proces 9: Terminarea Sesiunii (Logout)</h3>
        <p>Arată procesul de distrugere a Sesiunii (`session_destroy()`) și redirecționare.</p>
        <img src="imagini/logout.png" alt="Flow Logout">

    </div>
</body>
</html>