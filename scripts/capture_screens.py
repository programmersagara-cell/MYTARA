import time, os, sys
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By

BASE = "http://localhost/Itara"
LOGIN_USER = sys.argv[1]
PASSWORD = "admin123"
TAG = sys.argv[2]
OUT = os.path.join(r"c:\xampp\htdocs\Itara\docs\screenshots", TAG)
os.makedirs(OUT, exist_ok=True)

PAGES = [
    ("login",              "/login",                  "Login page"),
    ("dashboard",          "/dashboard",              "Dashboard"),
    ("topology",           "/topology",               "Network Topology"),
    ("assets",             "/assets",                 "Asset Inventory"),
    ("asset_create",       "/assets/create",          "Create Asset"),
    ("asset_detail",       "/assets/13",               "Asset Detail"),
    ("asset_edit",         "/assets/13/edit",          "Edit Asset"),
    ("asset_import",       "/assets/import",          "Bulk Import Assets"),
    ("assets_retired",     "/assets/retired",         "Retired Assets"),
    ("licenses",           "/licenses",               "Software Licenses"),
    ("license_create",     "/licenses/create",        "Create License"),
    ("license_detail",     "/licenses/8",             "License Detail"),
    ("license_edit",       "/licenses/8/edit",        "Edit License"),
    ("disposals",          "/disposals",              "Asset Disposals"),
    ("disposal_detail",    "/disposals/1",            "Disposal Detail"),
    ("departments",        "/departments",            "Departments"),
    ("history",            "/history",                "Asset History / Audit Trail"),
    ("messages",           "/messages",               "Messenger"),
    ("profile",            "/profile",                "My Profile"),
    ("users",              "/users",                  "User Management"),
    ("user_create",        "/users/create",           "Create User"),
    ("user_edit",          "/users/1/edit",           "Edit User"),
    ("settings",           "/settings",               "System Settings"),
    ("tickets",            "/tickets",                "IT Tickets (Submit Concern)"),
    ("ticket_edit",        "/tickets/82/edit",         "Edit Ticket"),
    ("tickets_my_history", "/tickets/my-history",     "My Ticket History"),
    ("tickets_manage",     "/tickets/manage",         "Ticket Management (Admin)"),
    ("tickets_history",    "/tickets/history",        "Ticket History (Admin)"),
    ("tickets_analytics",  "/tickets/analytics",      "Ticket Analytics"),
    ("tickets_instructions","/tickets/instructions",  "Ticket Instructions (Admin)"),
    ("tickets_backup",     "/tickets/backup",         "Incremental Backup (Admin)"),
]

def make_driver():
    opts = Options()
    opts.add_argument("--headless=new")
    opts.add_argument("--window-size=1600,1000")
    opts.add_argument("--hide-scrollbars")
    opts.add_argument("--force-device-scale-factor=1")
    opts.add_experimental_option("excludeSwitches", ["enable-logging"])
    return webdriver.Chrome(options=opts)

def shot(driver, name, path):
    time.sleep(2.2)  # let JS/CSS settle
    driver.save_screenshot(os.path.join(OUT, f"{name}.png"))

def main():
    driver = make_driver()
    try:
        # capture login page
        driver.get(BASE + "/login"); shot(driver, "login", None)

        # login
        driver.get(BASE + "/login")
        driver.find_element(By.ID, "login").send_keys(LOGIN_USER)
        driver.find_element(By.ID, "password").send_keys(PASSWORD)
        driver.find_element(By.ID, "submitBtn").click()
        time.sleep(3)
        cur = driver.current_url
        print("after login:", cur)
        if "/startup" in cur:
            driver.get(BASE + "/dashboard"); time.sleep(2)
        if "/login" in driver.current_url:
            print("LOGIN FAILED for", LOGIN_USER); sys.exit(2)

        for name, path, _desc in PAGES:
            if name == "login":
                continue
            try:
                driver.get(BASE + path)
                time.sleep(0.5)
                if "/login" in driver.current_url:
                    print("SKIP (redirect to login):", name)
                    continue
                if "error-container" in driver.page_source or "Internal Server Error" in driver.page_source:
                    print("SKIP (error):", name, driver.current_url)
                    continue
                shot(driver, name, None)
                print("OK:", name)
            except Exception as e:
                print("FAIL:", name, e)
    finally:
        driver.quit()

if __name__ == "__main__":
    main()
