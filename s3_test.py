import boto3

def main():

    # Create an S3 client using your AWS credentials
    s3 = boto3.client('s3')

    # Specify your local file path, the S3 bucket name, and the desired object key (filename) in S3
    local_file = 'html/index.html'  # Path to your local file
    bucket_name = 'baseball.tomgsmith.com'
    s3_key = 'temp/test2.html'  # This can include a folder path if needed

    # Upload the file to S3
    s3.upload_file(local_file, bucket_name, s3_key, ExtraArgs={'ACL': 'public-read', 'ContentType': 'text/html'})

    print(f"Uploaded {local_file} to s3://{bucket_name}/{s3_key}")
    
    exit()

if __name__ == "__main__":
    main()
