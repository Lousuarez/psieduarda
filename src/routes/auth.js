const express = require('express');
const { attemptLogin } = require('../auth');
const asyncHandler = require('../asyncHandler');

const router = express.Router();

router.post('/login', asyncHandler(async (req, res) => {
  const user = String(req.body.user || '').trim();
  const pass = String(req.body.pass || '');
  const ok = await attemptLogin(user, pass);
  if (ok) {
    req.session.regenerate((err) => {
      if (err) return res.redirect('/');
      req.session.authed = true;
      res.redirect('/');
    });
    return;
  }
  req.session.loginError = true;
  res.redirect('/');
}));

router.get('/logout', (req, res) => {
  req.session.destroy(() => {
    res.redirect('/');
  });
});

module.exports = router;
