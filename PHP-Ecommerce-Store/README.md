# PHP E-Commerce Store

A simple e-commerce website built with core PHP as a practice project, applying form validation, sessions, and Bootstrap for the UI.

**Live Demo:** [myecommercestore.rf.gd](http://myecommercestore.rf.gd)

## Features

- **Home page** — navbar with site branding and a welcome header banner.
- **All Products page** — 6 products rendered from a PHP associative array using a `foreach` loop, displayed as Bootstrap cards, with an "Add to Cart" button that shows a toast notification.
- **Account page** — two dynamic states based on session:
  - **Guest:** a login form (email & password) with server-side validation and inline error messages.
  - **Logged in:** a profile form (username, password, email, phone, Facebook/Twitter/Instagram URLs), each field validated individually.
- **Session-based auth** — form data is stored in `$_SESSION` on successful validation, with a logout link that destroys the session.
- **Dynamic navbar** — shows "Login" for guests, and "Account" + "Logout" for authenticated users.

## Tech Stack

- PHP (core, no frameworks)
- Bootstrap 4.4
- PHP Sessions

## Project Structure

```
├── index.php           # Home page
├── all-products.php     # Products listing (associative array + foreach)
├── account.php          # Login form / profile form (session-based state)
├── logout.php           # Destroys the session
├── includes/
│   ├── header.php       # Shared navbar + <head>
│   └── footer.php       # Shared footer + scripts
├── css/
│   └── style.css
└── images/
```

## Running Locally

```bash
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

## Author

**Osama Ahmed**

- Portfolio: [osama-portfolio-six.vercel.app](https://osama-portfolio-six.vercel.app/)
- GitHub: [Osama2214](https://github.com/Osama2214)
- LinkedIn: [osama-ahmed-67127222a](https://www.linkedin.com/in/osama-ahmed-67127222a/)
- Email: [osamahamad261981@gmail.com](mailto:osamahamad261981@gmail.com)
