/* Unit 10: File Handling in C
 * Error handling in files - checking fopen()'s return value and using
 * feof()/ferror() to detect end-of-file vs a real read error. */
#include <stdio.h>

int main(void) {
    FILE *fp = fopen("does_not_exist.txt", "r");

    if (fp == NULL) {
        perror("Error opening file"); /* prints a system error message */
        return 1;
    }

    char line[100];
    while (fgets(line, sizeof(line), fp) != NULL) {
        printf("%s", line);
    }

    if (feof(fp)) {
        printf("Reached end of file normally.\n");
    } else if (ferror(fp)) {
        printf("A read error occurred.\n");
    }

    fclose(fp);
    return 0;
}
