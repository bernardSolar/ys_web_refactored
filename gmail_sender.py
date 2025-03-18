import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication
import os
import argparse
from dotenv import load_dotenv


class SMTP2GOSender:
    def __init__(self, smtp_user, smtp_password):
        self.smtp_user = smtp_user
        self.smtp_password = smtp_password
        self.smtp_server = "mail.smtp2go.com"
        self.port = 2525

    def send_email(self, from_email, to_email, subject, body, attachments=None):
        if isinstance(to_email, str):
            to_email = [to_email]

        message = MIMEMultipart()
        message["From"] = from_email
        message["To"] = ", ".join(to_email)
        message["Subject"] = subject

        message.attach(MIMEText(body, "html"))

        if attachments:
            for file_path in attachments:
                if os.path.exists(file_path):
                    with open(file_path, "rb") as file:
                        part = MIMEApplication(file.read(), Name=os.path.basename(file_path))
                    part["Content-Disposition"] = f'attachment; filename="{os.path.basename(file_path)}"'
                    message.attach(part)
                else:
                    print(f"File not found: {file_path}")

        try:
            server = smtplib.SMTP(self.smtp_server, self.port)
            server.starttls()
            server.login(self.smtp_user, self.smtp_password)
            server.sendmail(from_email, to_email, message.as_string())
            server.quit()

            print("Email sent successfully via SMTP2GO!")
            return True

        except Exception as e:
            print(f"Failed to send email: {e}")
            return False


def main():
    load_dotenv()  # Load environment variables from .env file

    parser = argparse.ArgumentParser(description="Send emails via SMTP2GO")
    parser.add_argument("--smtp_user", default=os.getenv("SMTP_USER"), help="solarnautics.org")
    parser.add_argument("--smtp_password", default=os.getenv("SMTP_PASSWORD"), help="TOZ9zpjirCDQELug")
    parser.add_argument("--from_email", default=os.getenv("FROM_EMAIL"), help="bernard@solarnautics.org")
    parser.add_argument("--to", default=os.getenv("TO_EMAIL"), help="bernard.mccarty@gmail.com")
    parser.add_argument("--subject", default=os.getenv("SUBJECT"), help="Email subject")
    parser.add_argument("--body", default=os.getenv("BODY"), help="Email body (HTML supported)")
    parser.add_argument("--attachments", default=os.getenv("ATTACHMENTS"), help="")

    args = parser.parse_args()

    to_emails = [email.strip() for email in args.to.split(",")]
    attachments = [path.strip() for path in args.attachments.split(",")] if args.attachments else None

    sender = SMTP2GOSender(args.smtp_user, args.smtp_password)
    sender.send_email(args.from_email, to_emails, args.subject, args.body, attachments)


if __name__ == "__main__":
    main()
