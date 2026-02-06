# 📘 NUBL - Git Commit Guide

Simple commit message rules for clean Git history and easy code reviews.

---

## ✅ Format

```
<type>(<scope>): <summary> [FR-#]
```

**Example:**
```
feat(auth): add RBAC middleware [FR-1.5]
```

**Rules:**
- Use present tense (add, fix, NOT added/fixed)
- Keep summary ≤ 72 characters
- One commit = one change
- Add `[FR-#]` or `[NFR-#]` if related to requirements

---

## ✅ Types (Remember: F F R D T C)

| Type | Use When |
|------|----------|
| **feat** | New feature |
| **fix** | Bug fix |
| **refactor** | Code restructure (no behavior change) |
| **docs** | Documentation only |
| **test** | Tests only |
| **chore** | Setup/config/dependencies |

---

## ✅ Examples

```
feat(donations): create donation form [FR-2.1]
fix(auth): prevent unauthorized access [FR-1.5]
refactor(services): extract donation logic
docs(readme): add Spatie setup guide
test(auth): add role tests [FR-1.5]
chore(deps): install spatie/laravel-permission
```

---

## ✅ Common Scopes

- `auth`, `roles`, `users`
- `donations`, `requests`, `qr`
- `routes`, `views`, `ui`
- `db`, `migrations`, `seeders`
- `config`, `core`

---

## ✅ Optional Body

For complex changes, add details:

```
feat(auth): implement RBAC [FR-1.5]

- Added Spatie roles
- Protected routes with middleware
- Added role-based redirects
```

---

## ✅ Rules

**DO:**
- ✅ Write clear messages
- ✅ Keep commits small
- ✅ Reference FR/NFR when relevant
- ✅ One logical change per commit

**DON'T:**
- ❌ Vague messages ("update stuff")
- ❌ Mix unrelated changes
- ❌ Skip requirement mapping

---

## ✅ Quick Reference

**Format:** `type(scope): summary [FR-#]`  
**Types:** feat, fix, refactor, docs, test, chore  
**Always:** Present tense, clear, one change

---

**That's it! Keep it simple and consistent.** 🚀
